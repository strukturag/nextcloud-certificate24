<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, struktur AG.
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
namespace OCA\Esig;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\File;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class Verify {
	private LoggerInterface $logger;
	private IDBConnection $db;

	public function __construct(LoggerInterface $logger,
		IDBConnection $db) {
		$this->logger = $logger;
		$this->db = $db;
	}

	public function storeFileSignatures(File $file, ?array $signatures): void {
		if (empty($signatures)) {
			$this->deleteFileSignatures($file);
			return;
		}

		$signatures = json_encode($signatures);
		$update = $this->db->getQueryBuilder();
		$update->update('esig_file_signatures')
			->set('updated', $update->createFunction('now()'))
			->set('signatures', $update->createNamedParameter($signatures))
			->where($update->expr()->eq('file_id', $update->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		if ($update->executeStatement() > 0) {
			// Updated existing entry.
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->insert('esig_file_signatures')
			->values(
				[
					'file_id' => $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT),
					'created' => $query->createFunction('now()'),
					'updated' => $query->createFunction('now()'),
					'signatures' => $query->createNamedParameter($signatures),
				]
			);

		try {
			$query->executeStatement();
		} catch (UniqueConstraintViolationException $e) {
			// Another user added the entry concurrently.
			return;
		}
	}

	public function getFileSignatures(File $file): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_file_signatures')
			->where($query->expr()->eq('file_id', $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		return json_decode($row['signatures'], true);
	}

	public function deleteFileSignatures(File $file): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('esig_file_signatures')
			->where($query->expr()->eq('file_id', $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}
}
