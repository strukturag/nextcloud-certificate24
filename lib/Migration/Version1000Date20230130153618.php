<?php

namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20230130153618 extends SimpleMigrationStep {
	protected IDBConnection $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * {@inheritDoc}
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('esig_requests');
		if (!$table->hasColumn('email_sent')) {
			$table->addColumn('email_sent', Types::DATETIMETZ_MUTABLE, [
				'notnull' => false,
			]);
		}

		$table = $schema->getTable('esig_recipients');
		if (!$table->hasColumn('email_sent')) {
			$table->addColumn('email_sent', Types::DATETIMETZ_MUTABLE, [
				'notnull' => false,
			]);
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		// Assume we could send out emails in the past.
		$update = $this->db->getQueryBuilder();
		$update->update('esig_requests')
			->set('email_sent', 'created')
			->where($update->expr()->eq('recipient_type', $update->createNamedParameter('email')));
		$update->executeStatement();

		$update = $this->db->getQueryBuilder();
		$update->update('esig_recipients')
			->set('email_sent', 'created')
			->where($update->expr()->eq('type', $update->createNamedParameter('email')));
		$update->executeStatement();
	}
}
