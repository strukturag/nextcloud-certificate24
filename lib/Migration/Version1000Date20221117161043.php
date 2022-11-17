<?php
namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20221117161043 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		if (!$schema->hasTable('esig_file_metadata')) {
			$table = $schema->createTable('esig_file_metadata');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('created', Types::DATETIMETZ_MUTABLE, [
				'notnull' => true,
			]);
			$table->addColumn('updated', Types::DATETIMETZ_MUTABLE, [
				'notnull' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('metadata', Types::TEXT, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['file_id'], 'efm_file_id');
		}
		return $schema;
	}

}
