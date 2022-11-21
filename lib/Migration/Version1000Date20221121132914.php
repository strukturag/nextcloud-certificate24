<?php
namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20221121132914 extends SimpleMigrationStep {

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

		if (!$schema->hasTable('esig_recipients')) {
			$table = $schema->createTable('esig_recipients');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('request_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('created', Types::DATETIMETZ_MUTABLE, [
				'notnull' => true,
			]);
			$table->addColumn('signed', Types::DATETIMETZ_MUTABLE, [
				'notnull' => false,
			]);
			$table->addColumn('type', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('value', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueConstraint(['request_id', 'type', 'value'], 'recipients_unique_recipient');

			$requestsTable = $schema->getTable('esig_requests');
			$table->addForeignKeyConstraint($requestsTable, ['request_id'], ['id'], [
				'onDelete' => 'cascade',
				'onUpdate' => 'cascade',
			], 'fk_request_id');
		}
		return $schema;
	}

	/**
	 * {@inheritDoc}
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$select = $this->db->getQueryBuilder();
		$select->select('*')
			->from('esig_requests');

		$insert = $this->db->getQueryBuilder();
		$insert->insert('esig_recipients')
			->values(
				[
					'request_id' => $insert->createParameter('request_id'),
					'created' => $insert->createParameter('created'),
					'signed' => $insert->createParameter('signed'),
					'type' => $insert->createParameter('type'),
					'value' => $insert->createParameter('value'),
				]
			);

		$result = $select->executeQuery();
		while ($row = $result->fetch()) {
			$insert->setParameter('request_id', $row['id'])
				->setParameter('created', $row['created'])
				->setParameter('signed', $row['signed'])
				->setParameter('type', $row['recipient_type'])
				->setParameter('value', $row['recipient']);
			$insert->executeStatement();
		}
		$result->closeCursor();
	}

}
