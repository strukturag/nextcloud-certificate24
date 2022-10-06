<?php

declare(strict_types=1);

namespace OCA\Esig;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Esig\Config;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\File;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Security\ISecureRandom;

class Requests {

	private ILogger $logger;
	private ISecureRandom $secureRandom;
	private IDBConnection $db;

	public function __construct(ILogger $logger,
								ISecureRandom $secureRandom,
								IDBConnection $db) {
		$this->logger = $logger;
		$this->secureRandom = $secureRandom;
		$this->db = $db;
	}

  private function newRandomId(int $length): string {
		$chars = str_replace(['l', '0', '1'], '', ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
		return $this->secureRandom->generate($length, $chars);
	}

  public function storeRequest(File $file, IUser $user, string $recipient, string $recipient_type, array $account, string $server, string $esig_file_id): string {
		$query = $this->db->getQueryBuilder();
		$query->insert('esig_requests')
			->values(
				[
					'id' => $query->createParameter('id'),
					'file_id' => $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT),
					'created' => $query->createFunction('now()'),
					'user_id' => $query->createNamedParameter($user->getUID()),
					'recipient' => $query->createNamedParameter($recipient),
					'recipient_type' => $query->createNamedParameter($recipient_type),
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

		return $id;
  }

	public function getRequestById(string $id): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		return $row;
	}

	private function getOwnRequestFromRow($row, bool $include_signed): array {
		$r = [
			'request_id' => $row['id'],
			'created' => $row['created'],
			'file_id' => $row['file_id'],
			'recipient' => $row['recipient'],
			'recipient_type' => $row['recipient_type'],
		];
		if ($include_signed) {
			$r['signed'] = $row['signed'];
		}
		return $r;
	}

	public function getOwnRequests(IUser $user, bool $include_signed): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('user_id', $query->createNamedParameter($user->getUID())))
			->orderBy('created');

		if (!$include_signed) {
			$query->andWhere($query->expr()->isNull('signed'));
		}
		$result = $query->executeQuery();

		$requests = [];
		while ($row = $result->fetch()) {
			$requests[] = $this->getOwnRequestFromRow($row, $include_signed);
		}
		$result->closeCursor();

		return $requests;
	}

	public function getOwnRequestById(IUser $user, string $id): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->eq('user_id', $query->createNamedParameter($user->getUID())));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		return $this->getOwnRequestFromRow($row, true);
	}

	private function getIncomingRequestFromRow($row, bool $include_signed): array {
		$r = [
			'request_id' => $row['id'],
			'created' => $row['created'],
			'file_id' => $row['file_id'],
			'user_id' => $row['user_id'],
		];
		if ($include_signed) {
			$r['signed'] = $row['signed'];
		}
		return $r;
	}

	public function getIncomingRequests(IUser $user, bool $include_signed): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('recipient', $query->createNamedParameter($user->getUID())))
			->andWhere($query->expr()->eq('recipient_type', $query->createNamedParameter('user')))
			->orderBy('created');

		if (!$include_signed) {
			$query->andWhere($query->expr()->isNull('signed'));
		}
		$result = $query->executeQuery();

		$requests = [];
		while ($row = $result->fetch()) {
			$requests[] = $this->getIncomingRequestFromRow($row, $include_signed);
		}
		$result->closeCursor();

		return $requests;
	}

	public function markRequestSignedById(string $id) {
		$query = $this->db->getQueryBuilder();
		$query->update('esig_requests')
			->set('signed', $query->createFunction('now()'))
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
