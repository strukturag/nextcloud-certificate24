<?php

namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Types;
use OCA\Esig\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use Psr\Log\LoggerInterface;

class Version1000Date20230131095219 extends SimpleMigrationStep {
	public const ISO8601_EXTENDED = "Y-m-d\TH:i:s.uP";

	private LoggerInterface $logger;
	private IDBConnection $db;

	public function __construct(LoggerInterface $logger, IDBConnection $db) {
		$this->logger = $logger;
		$this->db = $db;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		$table = $schema->getTable('esig_recipients');
		if (!$table->hasColumn('saved')) {
			$table->addColumn('saved', Types::DATETIMETZ_MUTABLE, [
				'notnull' => false,
			]);
		}
		return $schema;
	}

	private function parseDateTime($s) {
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

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		// Make sure all requests have a saved timestamp set and flag downloads of
		// individual recipients with timestamp of main request.
		// While this is technically not correct, it will prevent the signed files
		// from being re-downloaded again.
		$query = $this->db->getQueryBuilder();
		$query->update('esig_requests')
			->set('saved', $query->createFunction('now()'))
			->where($query->expr()->isNotNull('signed'))
			->andWhere($query->expr()->isNull('saved'));
		$query->executeStatement();

		$query = $this->db->getQueryBuilder();
		$query->select('id', 'saved')
			->from('esig_requests')
			->where($query->expr()->isNotNull('saved'));
		$result = $query->executeQuery();

		$update = $this->db->getQueryBuilder();
		$update->update('esig_recipients')
			->set('saved', $update->createParameter('saved'))
			->where($update->expr()->isNotNull('signed'))
			->andWhere($update->expr()->eq('request_id', $update->createParameter('request_id')));

		while ($row = $result->fetch()) {
			$saved = $row['saved'];
			if (is_string($saved)) {
				$saved = $this->parseDateTime($saved);
			}
			$update->setParameter('saved', $saved, 'datetimetz');
			$update->setParameter('request_id', $row['id'], IQueryBuilder::PARAM_INT);
			$update->executeStatement();
		}
		$result->closeCursor();
	}
}
