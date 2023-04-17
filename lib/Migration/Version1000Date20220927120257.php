<?php

/**
 * @copyright Copyright (c) 2022, struktur AG.
 *
 * @author Joachim Bauch <bauch@struktur.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Esig\Migration;

use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20220927120257 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		if (!$schema->hasTable('esig_requests')) {
			$table = $schema->createTable('esig_requests');
			$table->addColumn('id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('created', Types::DATETIMETZ_MUTABLE, [
				'notnull' => true,
			]);
			$table->addColumn('signed', Types::DATETIMETZ_MUTABLE, [
				'notnull' => false,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('recipient', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('recipient_type', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('esig_account_id', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('esig_server', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('esig_file_id', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
		}
		return $schema;
	}
}
