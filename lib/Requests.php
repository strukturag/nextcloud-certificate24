<?php

declare(strict_types=1);

namespace OCA\Esig;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Esig\Config;
use OCA\Esig\Events\ShareEvent;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Security\ISecureRandom;

class Requests {

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

  private function newRandomId(int $length): string {
		$chars = str_replace(['l', '0', '1'], '', ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
		return $this->secureRandom->generate($length, $chars);
	}

  public function storeRequest(File $file, IUser $user, string $recipient, string $recipient_type, ?array $options, ?array $metadata, array $account, string $server, string $esig_file_id): string {
		$mime = $file->getMimeType();
		if ($mime) {
			$mime = strtolower($mime);
		}

		$signed_save_mode = $this->config->getSignedSaveMode();
		if ($options) {
			$signed_save_mode = $options['signed_save_mode'] ?? $signed_save_mode;
		}

		$query = $this->db->getQueryBuilder();
		$query->insert('esig_requests')
			->values(
				[
					'id' => $query->createParameter('id'),
					'file_id' => $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT),
					'filename' => $query->createNamedParameter($file->getName()),
					'mimetype' => $query->createNamedParameter($mime),
					'size' => $query->createNamedParameter($file->getSize()),
					'created' => $query->createFunction('now()'),
					'user_id' => $query->createNamedParameter($user->getUID()),
					'recipient' => $query->createNamedParameter($recipient),
					'recipient_type' => $query->createNamedParameter($recipient_type),
					'signed_save_mode' => $query->createNamedParameter($signed_save_mode),
					'metadata' => $query->createNamedParameter(!empty($metadata) ? json_encode($metadata) : null),
					'esig_account_id' => $query->createNamedParameter($account['id']),
					'esig_server' => $query->createNamedParameter($server),
					'esig_file_id' => $query->createNamedParameter($esig_file_id),
				]
			);

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

		$event = new ShareEvent($file, $user, $recipient, $recipient_type, $id);
		$this->dispatcher->dispatch(ShareEvent::class, $event);
		return $id;
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
		return $row;
	}

	public function getOwnRequests(IUser $user, bool $include_signed): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('user_id', $query->createNamedParameter($user->getUID())))
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
		return $row;
	}

	public function getIncomingRequests(IUser $user, bool $include_signed): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('recipient', $query->createNamedParameter($user->getUID())))
			->andWhere($query->expr()->eq('recipient_type', $query->createNamedParameter('user')))
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

			$requests[] = $row;
		}
		$result->closeCursor();

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

			$requests[] = $row;
		}
		$result->closeCursor();

		return $requests;
	}


	public function markRequestSignedById(string $id, \DateTime $now) {
		$query = $this->db->getQueryBuilder();
		$query->update('esig_requests')
			->set('signed', $query->createNamedParameter($now, 'datetimetz'))
			->where($query->expr()->eq('id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
		$query->executeStatement();
	}

	public function markRequestSavedById(string $id) {
		$query = $this->db->getQueryBuilder();
		$query->update('esig_requests')
			->set('saved', $query->createFunction('now()'))
			->where($query->expr()->eq('id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->isNull('saved'))
			->andWhere($query->expr()->eq('deleted', $query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
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
		$query->executeStatement();
	}

	public function mayAccess(?IUser $user, array $request): bool {
		switch ($request['recipient_type']) {
			case 'user':
				if (!$user) {
					return false;
				}
				if ($user->getUID() !== $request['recipient'] && $user->getUID() !== $request['user_id']) {
					// Only allowed to access if shared by or shared with current user.
					return false;
				}
				break;
			case 'email':
				// Allow anonymous access.
				break;
		}
		return true;
	}

}
