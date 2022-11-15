<?php
namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use OCA\Esig\Config;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20221115160418 extends SimpleMigrationStep {

	protected IDBConnection $db;

	public function __construct(IDBConnection $db) {
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

		$table = $schema->getTable('esig_requests');
		if (!$table->hasColumn('signed_save_mode')) {
			$table->addColumn('signed_save_mode', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
		}
		return $schema;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$update = $this->db->getQueryBuilder();
		$update->update('esig_requests')
			->set('signed_save_mode', $update->createNamedParameter(Config::DEFAULT_SAVE_MODE))
			->where($update->expr()->isNull('signed_save_mode'));
		$update->executeStatement();
	}

}
