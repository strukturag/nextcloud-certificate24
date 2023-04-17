<?php

/**
 * @copyright Copyright (c) 2023, struktur AG.
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
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

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
