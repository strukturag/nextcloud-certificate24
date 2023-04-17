<?php

namespace OCA\Esig\Migration;

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
				->from('esig_requests');
			$result = $query->executeQuery();
			$ids = [];
			while ($row = $result->fetch()) {
				$ids[] = $row['id'];
			}
			$result->closeCursor();

			$query = $this->db->getQueryBuilder();
			$query->delete('esig_recipients')
				->where($query->expr()->notIn('request_id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_STR_ARRAY)));
			$query->executeStatement();
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
