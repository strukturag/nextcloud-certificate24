<?php

declare(strict_types=1);

namespace OCA\Esig;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Esig\AppInfo\Application;
use OCA\Esig\Events\ShareEvent;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUser;
use OCP\Security\ISecureRandom;
use Throwable;

class Requests {
	public const ISO8601_EXTENDED = "Y-m-d\TH:i:s.uP";

	// Store signed result as new file next to the original file.
	public const MODE_SIGNED_NEW = 'new';
	// Update original file with signed result.
	public const MODE_SIGNED_REPLACE = 'replace';
	// Don't process signed result.
	public const MODE_SIGNED_NONE = 'none';

	private ILogger $logger;
	private ISecureRandom $secureRandom;
	private IDBConnection $db;
	private IEventDispatcher $dispatcher;
	private Config $config;

	public function __construct(ILogger $logger,
								ISecureRandom $secureRandom,
								IDBConnection $db,
								IEventDispatcher $dispatcher,
								Config $config) {
		$this->logger = $logger;
		$this->secureRandom = $secureRandom;
		$this->db = $db;
		$this->dispatcher = $dispatcher;
		$this->config = $config;
	}

	public function parseDateTime($s) {
		if (!$s) {
			return null;
		}
		if ($s[strlen($s) - 1] === 'Z') {
			$s = substr($s, 0, strlen($s) - 1) . '+00:00';
		}
		if ($s[strlen($s) - 3] !== ':') {
			$s = $s . ':00';
		}
		if ($s[10] === ' ') {
			$s[10] = 'T';
		}
		if (strlen($s) === 19) {
			// SQLite backend stores without timezone, e.g. "2022-10-12 06:54:54".
			$s .= '+00:00';
		}
		$dt = \DateTime::createFromFormat(\DateTime::ISO8601, $s);
		if (!$dt) {
			$dt = \DateTime::createFromFormat(self::ISO8601_EXTENDED, $s);
		}
		if (!$dt) {
			$this->logger->error('Could not convert ' . $s . ' to datetime', [
				'app' => Application::APP_ID,
			]);
			$dt = null;
		}
		return $dt;
	}

	private function newRandomId(int $length): string {
		$chars = str_replace(['l', '0', '1'], '', ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
		return $this->secureRandom->generate($length, $chars);
	}

	public function storeRequest(File $file, IUser $user, array $recipients, ?array $options, ?array $metadata, array $account, string $server, string $esig_file_id, ?string $esig_signature_result_id): string {
		$mime = $file->getMimeType();
		if ($mime) {
			$mime = strtolower($mime);
		}

		$signed_save_mode = $this->config->getSignedSaveMode();
		if ($options) {
			$signed_save_mode = $options['signed_save_mode'] ?? $signed_save_mode;
		}

		$query = $this->db->getQueryBuilder();
		$values = [
			'id' => $query->createParameter('id'),
			'file_id' => $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT),
			'filename' => $query->createNamedParameter($file->getName()),
			'mimetype' => $query->createNamedParameter($mime),
			'size' => $query->createNamedParameter($file->getSize()),
			'created' => $query->createFunction('now()'),
			'user_id' => $query->createNamedParameter($user->getUID()),
			'signed_save_mode' => $query->createNamedParameter($signed_save_mode),
			'metadata' => $query->createNamedParameter(!empty($metadata) ? json_encode($metadata) : null),
			'esig_account_id' => $query->createNamedParameter($account['id']),
			'esig_server' => $query->createNamedParameter($server),
			'esig_file_id' => $query->createNamedParameter($esig_file_id),
			'esig_signature_result_id' => $query->createNamedParameter($esig_signature_result_id),
		];
		if (count($recipients) === 1) {
			$values['recipient'] = $query->createNamedParameter($recipients[0]['value']);
			$values['recipient_type'] = $query->createNamedParameter($recipients[0]['type']);
			$values['esig_signature_id'] = $query->createNamedParameter($recipients[0]['public_id'] ?? null);
		}
		$query->insert('esig_requests')
			->values($values);

		$id = $this->newRandomId(16);
		while (true) {
			$query->setParameter('id', $id);
			try {
				$query->executeStatement();
			} catch (UniqueConstraintViolationException $e) {
				// Duplicate id, generate new.
				$id = $this->newRandomId(16);
				continue;
			}

			break;
		}

		if (count($recipients) > 1) {
			$insert = $this->db->getQueryBuilder();
			$insert->insert('esig_recipients')
				->values(
					[
						'request_id' => $insert->createNamedParameter($id),
						'created' => $insert->createFunction('now()'),
						'type' => $insert->createParameter('type'),
						'value' => $insert->createParameter('value'),
						'esig_signature_id' => $insert->createParameter('esig_signature_id'),
					]
				);
			foreach ($recipients as $recipient) {
				$insert->setParameter('type', $recipient['type']);
				$insert->setParameter('value', $recipient['value']);
				$insert->setParameter('esig_signature_id', $recipient['public_id'] ?? null);
				$insert->executeStatement();
			}
		}

		$event = new ShareEvent($file, $user, $recipients, $id);
		$this->dispatcher->dispatch(ShareEvent::class, $event);
		return $id;
	}

	private function getRecipients(array $row): ?array {
		if ($row['recipient']) {
			$signed = $row['signed'];
			if (is_string($signed)) {
				$signed = $this->parseDateTime($signed);
			}
			return [
				[
					'type' => $row['recipient_type'],
					'value' => $row['recipient'],
					'esig_signature_id' => $row['esig_signature_id'],
					'signed' => $signed,
				],
			];
		}

		$query = $this->db->getQueryBuilder();
		$query->select('type', 'value', 'esig_signature_id', 'signed')
			->from('esig_recipients')
			->where($query->expr()->eq('request_id', $query->createNamedParameter($row['id'])))
			->orderBy('id');
		$result = $query->executeQuery();

		$recipients = [];
		while ($row = $result->fetch()) {
			$signed = $row['signed'];
			if (is_string($signed)) {
				$signed = $this->parseDateTime($signed);
			}
			$row['signed'] = $signed;
			$recipients[] = $row;
		}
		$result->closeCursor();
		return $recipients;
	}

	public function getRequestById(string $id): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		if ($row['metadata']) {
			$row['metadata'] = json_decode($row['metadata'], true);
		}
		$row['recipients'] = $this->getRecipients($row);
		return $row;
	}

	public function getRequestByEsigFileId(string $fileId): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('esig_file_id', $query->createNamedParameter($fileId)))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		if ($row['metadata']) {
			$row['metadata'] = json_decode($row['metadata'], true);
		}
		$row['recipients'] = $this->getRecipients($row);
		return $row;
	}

	public function getRequestByEsigSignatureId(string $signatureId): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('esig_signature_id', $query->createNamedParameter($signatureId)))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row) {
			// Simple case, one recipient.
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}
			$row['recipients'] = $this->getRecipients($row);
			return $row;
		}

		$query = $this->db->getQueryBuilder();
		$query->select('request_id')
			->from('esig_recipients')
			->where($query->expr()->eq('esig_signature_id', $query->createNamedParameter($signatureId)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		return $this->getRequestById($row['request_id']);
	}

	public function getOwnRequests(IUser $user, bool $include_signed): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('user_id', $query->createNamedParameter($user->getUID())))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
			->orderBy('created');

		$result = $query->executeQuery();

		$requests = [];
		while ($row = $result->fetch()) {
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}

			$row['recipients'] = $this->getRecipients($row);
			$allSigned = true;
			if (!$include_signed) {
				foreach ($row['recipients'] as $recipient) {
					if (!$recipient['signed']) {
						$allSigned = false;
						break;
					}
				}

				if ($allSigned) {
					continue;
				}
			}
			$requests[] = $row;
		}
		$result->closeCursor();

		return $requests;
	}

	public function getOwnRequestById(IUser $user, string $id): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->eq('user_id', $query->createNamedParameter($user->getUID())))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		if ($row['metadata']) {
			$row['metadata'] = json_decode($row['metadata'], true);
		}
		$row['recipients'] = $this->getRecipients($row);
		return $row;
	}

	public function getIncomingRequests(IUser $user, bool $include_signed): array {
		$requests = [];

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('recipient', $query->createNamedParameter($user->getUID())))
			->andWhere($query->expr()->eq('recipient_type', $query->createNamedParameter('user')))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}

			$row['recipients'] = $this->getRecipients($row);
			$allSigned = true;
			if (!$include_signed) {
				foreach ($row['recipients'] as $recipient) {
					if (!$recipient['signed']) {
						$allSigned = false;
						break;
					}
				}

				if ($allSigned) {
					continue;
				}
			}
			$requests[] = $row;
		}
		$result->closeCursor();

		$query = $this->db->getQueryBuilder();
		$query->select('r.*')
			->from('esig_requests', 'r')
			->join('r', 'esig_recipients', 'p', 'r.id = p.request_id')
			->where($query->expr()->eq('p.value', $query->createNamedParameter($user->getUID())))
			->andWhere($query->expr()->eq('p.type', $query->createNamedParameter('user')))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
		$result = $query->executeQuery();

		while ($row = $result->fetch()) {
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}

			// TODO: Get from joined query directly.
			$row['recipients'] = $this->getRecipients($row);
			$allSigned = true;
			if (!$include_signed) {
				foreach ($row['recipients'] as $recipient) {
					if (!$recipient['signed']) {
						$allSigned = false;
						break;
					}
				}

				if ($allSigned) {
					continue;
				}
			}
			$requests[] = $row;
		}
		$result->closeCursor();

		usort($requests, function ($a, $b) {
			if ($a['created'] < $b['created']) {
				return -1;
			} elseif ($a['created'] > $b['created']) {
				return 1;
			} else {
				return 0;
			}
		});
		return $requests;
	}

	public function getRequestsForFile(File $file, bool $include_signed): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('file_id', $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
			->orderBy('created');

		if (!$include_signed) {
			$query->andWhere($query->expr()->isNull('signed'));
		}
		$result = $query->executeQuery();

		$requests = [];
		while ($row = $result->fetch()) {
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}

			$row['recipients'] = $this->getRecipients($row);
			if (!$include_signed && count($row['recipients']) > 1) {
				$allSigned = true;
				foreach ($row['recipients'] as $r) {
					if (!$r['signed']) {
						$allSigned = false;
						break;
					}
				}

				if ($allSigned) {
					continue;
				}
			}
			$requests[] = $row;
		}
		$result->closeCursor();

		return $requests;
	}

	public function markRequestSignedById(string $id, string $type, string $value, \DateTime $now): bool {
		$this->db->beginTransaction();
		try {
			$query = $this->db->getQueryBuilder();
			$query->update('esig_requests')
				->set('signed', $query->createNamedParameter($now, 'datetimetz'))
				->where($query->expr()->eq('id', $query->createNamedParameter($id)))
				->andWhere($query->expr()->eq('recipient_type', $query->createNamedParameter($type)))
				->andWhere($query->expr()->eq('recipient', $query->createNamedParameter($value)));
			if ($query->executeStatement() === 1) {
				// Single recipient for this request.
				$this->db->commit();
				return true;
			}

			$query = $this->db->getQueryBuilder();
			$query->update('esig_recipients')
				->set('signed', $query->createNamedParameter($now, 'datetimetz'))
				->where($query->expr()->eq('request_id', $query->createNamedParameter($id)))
				->andWhere($query->expr()->eq('type', $query->createNamedParameter($type)))
				->andWhere($query->expr()->eq('value', $query->createNamedParameter($value)));
			$query->executeStatement();

			$query = $this->db->getQueryBuilder();
			$query->select($query->func()->count('*', 'count'))
				->from('esig_recipients')
				->where($query->expr()->eq('request_id', $query->createNamedParameter($id)))
				->andWhere($query->expr()->isNull('signed'));
			$result = $query->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();
			$this->db->commit();

			return ((int) $row['count']) === 0;
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	public function markRequestSavedById(string $id) {
		$query = $this->db->getQueryBuilder();
		$query->update('esig_requests')
			->set('saved', $query->createFunction('now()'))
			->where($query->expr()->eq('id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->isNull('saved'));
		$query->executeStatement();
	}

	public function markRequestDeletedById(string $id) {
		$query = $this->db->getQueryBuilder();
		$query->update('esig_requests')
			->set('deleted', $query->createNamedParameter(true))
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$query->executeStatement();
	}

	public function deleteRequestById(string $id) {
		$query = $this->db->getQueryBuilder();
		$query->delete('esig_requests')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		if (!$query->executeStatement()) {
			return;
		}

		// Explicitly delete recipients for databases without foreign keys.
		$query = $this->db->getQueryBuilder();
		$query->delete('esig_recipients')
			->where($query->expr()->eq('request_id', $query->createNamedParameter($id)));
		$query->executeStatement();
	}

	public function mayAccess(?IUser $user, array $request): bool {
		if ($user) {
			if ($user->getUID() === $request['user_id']) {
				// Request was created by the user.
				return true;
			}

			// Check if request was sent to this user.
			if ($request['recipient_type'] === 'user' && $request['recipient'] === $user->getUID()) {
				return true;
			}

			$query = $this->db->getQueryBuilder();
			$query->select('id')
				->from('esig_recipients')
				->where($query->expr()->eq('request_id', $query->createNamedParameter($request['id'])))
				->andWhere($query->expr()->eq('type', $query->createNamedParameter('user')))
				->andWhere($query->expr()->eq('value', $query->createNamedParameter($user->getUID())));
			$result = $query->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();
			if ($row) {
				return true;
			}
		}

		// Check if any email was requested.
		// TODO: Explicitly check for email address.
		if ($request['recipient_type'] === 'email') {
			return true;
		}

		$query = $this->db->getQueryBuilder();
		$query->select('esig_recipients')
			->where($query->expr()->eq('request_id', $query->createNamedParameter($request['id'])))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter('email')));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row) {
			return true;
		}

		return false;
	}

	public function markEmailSent(string $id, string $email) {
		$query = $this->db->getQueryBuilder();
		$query->update('esig_requests')
			->set('email_sent', $query->createFunction('now()'))
			->where($query->expr()->eq('id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->eq('recipient_type', $query->createNamedParameter('email')))
			->andWhere($query->expr()->eq('recipient', $query->createNamedParameter($email)));
		if ($query->executeStatement() === 1) {
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->update('esig_recipients')
			->set('email_sent', $query->createFunction('now()'))
			->where($query->expr()->eq('request_id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter('email')))
			->andWhere($query->expr()->eq('value', $query->createNamedParameter($email)));
		$query->executeStatement();
	}

	public function getPendingEmails() {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('recipient_type', $query->createNamedParameter('email')))
			->andWhere($query->expr()->isNull('email_sent'));
		$result = $query->executeQuery();

		$pending = [];
		$recipients = [];
		while ($row = $result->fetch()) {
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}

			$row['recipients'] = $this->getRecipients($row);
			$recipients[] = $row;
		}
		$result->closeCursor();
		$pending['single'] = $recipients;

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_recipients')
			->where($query->expr()->eq('type', $query->createNamedParameter('email')))
			->andWhere($query->expr()->isNull('email_sent'));
		$result = $query->executeQuery();
		$recipients = [];
		$requests = [];
		while ($row = $result->fetch()) {
			if (!isset($requests[$row['request_id']])) {
				$requests[$row['request_id']] = $this->getRequestById($row['request_id']);
				if (!$requests[$row['request_id']]) {
					$this->logger->warning('Request ' . $row['request_id'] . ' no longer exists for pending email of ' . $row['type'] . ' ' . $row['value'], [
						'app' => Application::APP_ID,
					]);
					continue;
				}
			}
			$signed = $row['signed'];
			if (is_string($signed)) {
				$row['signed'] = $this->parseDateTime($signed);
			}
			$row['request'] = $requests[$row['request_id']];
			$recipients[] = $row;
		}
		$result->closeCursor();
		$pending['multi'] = $recipients;
		return $pending;
	}

	public function getPendingDownloads() {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->isNotNull('signed'))
			->andWhere($query->expr()->isNull('saved'));
		$result = $query->executeQuery();

		$pending = [];
		while ($row = $result->fetch()) {
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}

			$row['recipients'] = $this->getRecipients($row);
			$signed = $row['signed'];
			if (is_string($signed)) {
				$signed = $this->parseDateTime($signed);
			}
			$row['last_signed'] = $signed;
			$pending[] = $row;
		}
		$result->closeCursor();

		$query = $this->db->getQueryBuilder();
		$query->select('r.*')
			->from('esig_requests', 'r')
			->where($query->expr()->isNull('r.recipient'))
			->andWhere($query->expr()->isNull('r.saved'))
			->andWhere('not exists (select * from oc_esig_recipients p where r.id = p.request_id and p.signed is null)');
		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}

			$row['recipients'] = $this->getRecipients($row);
			$last_signed = null;
			foreach ($row['recipients'] as $recipient) {
				$signed = $recipient['signed'] ?? null;
				if (is_string($signed)) {
					$signed = $this->parseDateTime($signed);
				}
				if (!$signed) {
					continue;
				}
				if (!$last_signed || $last_signed < $signed) {
					$last_signed = $signed;
				}
			}
			if (!$last_signed) {
				// Should not happen, based on the query all recipients should have signed the request.
				continue;
			}
			$row['last_signed'] = $last_signed;
			$pending[] = $row;
		}
		$result->closeCursor();
		return $pending;
	}

	public function getPendingSignatures() {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->isNull('signed'))
			->andWhere($query->expr()->eq('recipient_type', $query->createNamedParameter('email')));
		$result = $query->executeQuery();

		$pending = [];
		$recipients = [];
		while ($row = $result->fetch()) {
			if ($row['metadata']) {
				$row['metadata'] = json_decode($row['metadata'], true);
			}

			$row['recipients'] = $this->getRecipients($row);
			$recipients[] = $row;
		}
		$result->closeCursor();
		$pending['single'] = $recipients;

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_recipients')
			->where($query->expr()->isNull('signed'))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter('email')));
		$result = $query->executeQuery();
		$recipients = [];
		$requests = [];
		while ($row = $result->fetch()) {
			if (!isset($requests[$row['request_id']])) {
				$requests[$row['request_id']] = $this->getRequestById($row['request_id']);
				if (!$requests[$row['request_id']]) {
					$this->logger->warning('Request ' . $row['request_id'] . ' no longer exists for pending signature of ' . $row['type'] . ' ' . $row['value'], [
						'app' => Application::APP_ID,
					]);
					continue;
				}
			}
			$row['request'] = $requests[$row['request_id']];
			$recipients[] = $row;
		}
		$result->closeCursor();
		$pending['multi'] = $recipients;
		return $pending;
	}
}
