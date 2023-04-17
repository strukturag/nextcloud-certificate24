<?php

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
namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Types;
use OC\User\NoUserException;
use OCP\DB\ISchemaWrapper;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20221018102419 extends SimpleMigrationStep {
	protected IDBConnection $db;
	protected IRootFolder $root;

	public function __construct(IDBConnection $db, IRootFolder $root) {
		$this->db = $db;
		$this->root = $root;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		$table = $schema->getTable('esig_requests');
		if (!$table->hasColumn('filename')) {
			$table->addColumn('filename', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
		}
		if (!$table->hasColumn('mimetype')) {
			$table->addColumn('mimetype', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
		}
		if (!$table->hasColumn('size')) {
			$table->addColumn('size', Types::INTEGER, [
				'notnull' => false,
			]);
		}
		return $schema;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$update = $this->db->getQueryBuilder();
		$update->update('esig_requests')
			->set('filename', $update->createParameter('filename'))
			->set('mimetype', $update->createParameter('mimetype'))
			->set('size', $update->createParameter('size'))
			->where($update->expr()->eq('id', $update->createParameter('id')));

		$query = $this->db->getQueryBuilder();
		$query->select('id', 'file_id', 'user_id')
			->from('esig_requests');
		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			$update->setParameter('id', $row['id']);

			$file_id = $row['file_id'];
			try {
				$folder = $this->root->getUserFolder($row['user_id']);
				$files = $folder->getById($file_id);
			} catch (NoUserException $e) {
				$files = [];
			} catch (NotPermittedException $e) {
				$files = [];
			}
			if (empty($files)) {
				$update->setParameter('filename', '');
				$update->setParameter('mimetype', '');
				$update->setParameter('size', -1);
				$update->executeStatement();
				continue;
			}

			$file = $files[0];
			$mime = $file->getMimeType();
			if ($mime) {
				$mime = strtolower($mime);
			}

			$update->setParameter('filename', $file->getName());
			$update->setParameter('mimetype', $mime);
			$update->setParameter('size', $file->getSize());
			$update->executeStatement();
		}
		$result->closeCursor();
	}
}
