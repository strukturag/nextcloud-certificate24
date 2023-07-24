<?php

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
namespace OCA\Certificate24\Migration;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Throwable;

class Version1000Date20230213115732 extends SimpleMigrationStep {
	protected IDBConnection $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		// Delete recipients entries for no-longer existing requests.
		$this->db->beginTransaction();
		try {
			$query = $this->db->getQueryBuilder();
			$query->select('id')
				->from('c24_requests');
			$result = $query->executeQuery();
			$ids = [];
			while ($row = $result->fetch()) {
				$ids[] = $row['id'];
			}
			$result->closeCursor();

			$query = $this->db->getQueryBuilder();
			$query->delete('c24_recipients')
				->where($query->expr()->notIn('request_id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_STR_ARRAY)));
			$query->executeStatement();
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
