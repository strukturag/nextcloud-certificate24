<?php

namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20221024145726 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		$table = $schema->getTable('esig_requests');
		if (!$table->hasColumn('metadata')) {
			$table->addColumn('metadata', Types::TEXT, [
				'notnull' => false,
			]);
		}
		return $schema;
	}
}
