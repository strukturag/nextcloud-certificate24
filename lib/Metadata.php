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

class Metadata {

	private ILogger $logger;
	private IDBConnection $db;
	private IEventDispatcher $dispatcher;

	public function __construct(ILogger $logger,
								IDBConnection $db,
								IEventDispatcher $dispatcher) {
		$this->logger = $logger;
		$this->db = $db;
		$this->dispatcher = $dispatcher;
	}

	public function storeMetadata(IUser $user, File $file, ?array $metadata): void {
		if (empty($metadata)) {
			$this->deleteMetadata($user, $file);
			return;
		}

		$update = $this->db->getQueryBuilder();
		$update->update('esig_file_metadata')
			->set('updated', $update->createFunction('now()'))
			->set('user_id', $update->createNamedParameter($user->getUID()))
			->set('metadata', $update->createNamedParameter(!empty($metadata) ? json_encode($metadata) : null))
			->where($update->expr()->eq('file_id', $update->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		if ($update->executeStatement() > 0) {
			// Updated existing entry.
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->insert('esig_file_metadata')
			->values(
				[
					'file_id' => $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT),
					'created' => $query->createFunction('now()'),
					'updated' => $query->createFunction('now()'),
					'user_id' => $query->createNamedParameter($user->getUID()),
					'metadata' => $query->createNamedParameter(!empty($metadata) ? json_encode($metadata) : null),
				]
			);

		try {
			$query->executeStatement();
		} catch (UniqueConstraintViolationException $e) {
			// Another user added the entry concurrently.
			return;
		}
	}

	public function getMetadata(IUser $user, File $file): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_file_metadata')
			->where($query->expr()->eq('file_id', $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		if ($row['metadata']) {
			$row['metadata'] = json_decode($row['metadata'], true);
		}
		return $row['metadata'];
	}

	public function deleteMetadata(IUser $user, File $file): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('esig_file_metadata')
			->where($query->expr()->eq('file_id', $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

}
