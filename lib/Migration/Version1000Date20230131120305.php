<?php

namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20230131120305 extends SimpleMigrationStep {
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
		if (!$table->hasColumn('esig_signature_id')) {
			$table->addColumn('esig_signature_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
		}

		$table = $schema->getTable('esig_recipients');
		if (!$table->hasColumn('esig_signature_id')) {
			$table->addColumn('esig_signature_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
		}

		return $schema;
	}
}
