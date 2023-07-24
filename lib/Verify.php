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
namespace OCA\Certificate24;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\File;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class Verify {
	private LoggerInterface $logger;
	private IDBConnection $db;
	private IMimeTypeLoader $mimeTypeLoader;
	private Requests $requests;

	public function __construct(LoggerInterface $logger,
		IDBConnection $db,
		IMimeTypeLoader $mimeTypeLoader,
		Requests $requests) {
		$this->logger = $logger;
		$this->db = $db;
		$this->mimeTypeLoader = $mimeTypeLoader;
		$this->requests = $requests;
	}

	public function storeFileSignatures(File $file, ?array $signatures): void {
		if (empty($signatures)) {
			$this->deleteFileSignatures($file);
			return;
		}

		$signatures = json_encode($signatures);
		$update = $this->db->getQueryBuilder();
		$update->update('c24_file_signatures')
			->set('updated', $update->createFunction('now()'))
			->set('signatures', $update->createNamedParameter($signatures))
			->where($update->expr()->eq('file_id', $update->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		if ($update->executeStatement() > 0) {
			// Updated existing entry.
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->insert('c24_file_signatures')
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
			->from('c24_file_signatures')
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
		$query->delete('c24_file_signatures')
			->where($query->expr()->eq('file_id', $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	public function deleteAllFileSignatures(): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('c24_file_signatures');
		$query->executeStatement();
	}

	public function getLastVerified(): ?\DateTime {
		$query = $this->db->getQueryBuilder();
		$query->selectAlias($query->func()->max('updated'), 'last')
			->from('c24_file_signatures');
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		return $this->requests->parseDateTime($row['last']);
	}

	public function getUnverifiedCount(): ?int {
		$pdfMimeTypeId = $this->mimeTypeLoader->getId('application/pdf');

		$query = $this->db->getQueryBuilder();
		$query->selectAlias($query->func()->count('*'), 'count')
			->from('filecache', 'fc')
			->leftJoin('fc', 'c24_file_signatures', 'fs', $query->expr()->eq('fc.fileid', 'fs.file_id'))
			->where($query->expr()->isNull('fs.file_id'))
			->andWhere($query->expr()->eq('mimetype', $query->expr()->literal($pdfMimeTypeId)))
			->andWhere($query->expr()->like('path', $query->expr()->literal('files/%')));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		return $row['count'];
	}
}
