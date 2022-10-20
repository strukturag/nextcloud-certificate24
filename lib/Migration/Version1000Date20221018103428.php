<?php
namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20221018103428 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		$this->ensureColumnIsNullable($schema, 'esig_requests', 'filename');
		$this->ensureColumnIsNullable($schema, 'esig_requests', 'mimetype');
		$this->ensureColumnIsNullable($schema, 'esig_requests', 'size');
		return $schema;
	}

	protected function ensureColumnIsNullable(ISchemaWrapper $schema, string $tableName, string $columnName): bool {
		$table = $schema->getTable($tableName);
		$column = $table->getColumn($columnName);

		if ($column->getNotnull()) {
			$column->setNotnull(false);
			return true;
		}

		return false;
	}

}
