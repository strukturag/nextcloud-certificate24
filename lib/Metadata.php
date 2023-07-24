<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, struktur AG.
 *
 * @author Joachim Bauch <bauch@struktur.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Certificate24;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\IDBConnection;
use OCP\IUser;
use Psr\Log\LoggerInterface;

class Metadata {
	private LoggerInterface $logger;
	private IDBConnection $db;
	private IEventDispatcher $dispatcher;

	public function __construct(LoggerInterface $logger,
		IDBConnection $db,
		IEventDispatcher $dispatcher) {
		$this->logger = $logger;
		$this->db = $db;
		$this->dispatcher = $dispatcher;
	}

	public function storeMetadata(IUser $user, File $file, ?array $metadata): void {
		if (empty($metadata)) {
			$this->deleteMetadata($file);
			return;
		}

		$update = $this->db->getQueryBuilder();
		$update->update('c24_file_metadata')
			->set('updated', $update->createFunction('now()'))
			->set('user_id', $update->createNamedParameter($user->getUID()))
			->set('metadata', $update->createNamedParameter(!empty($metadata) ? json_encode($metadata) : null))
			->where($update->expr()->eq('file_id', $update->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		if ($update->executeStatement() > 0) {
			// Updated existing entry.
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->insert('c24_file_metadata')
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
			->from('c24_file_metadata')
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

	public function deleteMetadata(File $file): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('c24_file_metadata')
			->where($query->expr()->eq('file_id', $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}
}
