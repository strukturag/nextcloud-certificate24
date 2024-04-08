<?php

/**
 * @copyright Copyright (c) 2024, struktur AG.
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
namespace OCA\Certificate24\Tests\php;

use OCA\Certificate24\Manager;
use Test\TestCase;

class ManagerTest extends TestCase {

	public function testSafeFilename() {
		$tests = [
			'test.pdf' => 'test.pdf',
			'test on 12:34:56.pdf' => 'test on 12.34.56.pdf',
			'test foo/bar.pdf' => 'test foo/bar.pdf',
			'test foo\\bar.pdf' => 'test foo_bar.pdf',
		];
		foreach ($tests as $filename => $expected) {
			$value = Manager::safeFilename($filename);
			$this->assertEquals($expected, $value);
		}
	}

}
