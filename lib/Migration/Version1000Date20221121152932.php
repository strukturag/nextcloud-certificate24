<?php
namespace OCA\Esig\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20221121152932 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('esig_requests');
		if ($table->hasColumn('recipient')) {
			$table->dropColumn('recipient');
		}
		if ($table->hasColumn('recipient_type')) {
			$table->dropColumn('recipient_type');
		}
		if ($table->hasColumn('signed')) {
			$table->dropColumn('signed');
		}
		return $schema;
	}

}
