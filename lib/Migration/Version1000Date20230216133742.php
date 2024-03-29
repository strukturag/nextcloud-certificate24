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
namespace OCA\Certificate24\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;
use Throwable;

class Version1000Date20230216133742 extends SimpleMigrationStep {
	public const ISO8601_EXTENDED = "Y-m-d\TH:i:s.uP";

	private LoggerInterface $logger;
	private IDBConnection $db;

	public function __construct(LoggerInterface $logger, IDBConnection $db) {
		$this->logger = $logger;
		$this->db = $db;
	}

	private function parseDateTime($s) {
		if (!$s) {
			return null;
		}
		if ($s[strlen($s) - 1] === 'Z') {
			$s = substr($s, 0, strlen($s) - 1) . '+00:00';
		}
		if ($s[strlen($s) - 3] !== ':') {
			$s = $s . ':00';
		}
		if ($s[10] === ' ') {
			$s[10] = 'T';
		}
		if (strlen($s) === 19) {
			// SQLite backend stores without timezone, e.g. "2022-10-12 06:54:54".
			$s .= '+00:00';
		}
		$dt = \DateTime::createFromFormat(\DateTime::ISO8601, $s);
		if (!$dt) {
			$dt = \DateTime::createFromFormat(self::ISO8601_EXTENDED, $s);
		}
		if (!$dt) {
			$this->logger->error('Could not convert ' . $s . ' to datetime');
			$dt = null;
		}
		return $dt;
	}

	/**
	 * {@inheritDoc}
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$this->db->beginTransaction();
		try {
			$query = $this->db->getQueryBuilder();
			$query->select('request_id', 'signed')
				->from('c24_recipients')
				->orderBy('request_id')
				->addOrderBy('signed');
			$result = $query->executeQuery();
			$entries = [];
			$currentRequest = null;
			$allSigned = true;
			$lastSigned = null;
			while ($row = $result->fetch()) {
				$id = $row['request_id'];
				if ($id !== $currentRequest) {
					if ($currentRequest && $allSigned && $lastSigned) {
						$entries[$currentRequest] = $lastSigned;
					}
					$currentRequest = $id;
					$allSigned = true;
					$lastSigned = null;
				}

				$signed = $row['signed'];
				if (!$signed) {
					$allSigned = false;
					continue;
				}

				if (is_string($signed)) {
					$signed = $this->parseDateTime($signed);
				}

				if (!$lastSigned || $lastSigned < $signed) {
					$lastSigned = $signed;
				}
			}
			if ($currentRequest && $allSigned && $lastSigned) {
				$entries[$currentRequest] = $lastSigned;
			}
			$result->closeCursor();

			$update = $this->db->getQueryBuilder();
			$update->update('c24_requests')
				->set('signed', $update->createParameter('signed'))
				->where($update->expr()->eq('id', $update->createParameter('id')))
				->andWhere($update->expr()->isNull('signed'));

			foreach ($entries as $id => $lastSigned) {
				$update->setParameter('signed', $lastSigned, 'datetimetz');
				$update->setParameter('id', $id);
				$update->executeStatement();
			}

			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
