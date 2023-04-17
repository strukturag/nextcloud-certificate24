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
namespace OCA\Esig\Tests\php;

use OCA\Esig\Validator;
use Test\TestCase;

class ValidatorTest extends TestCase {
	protected Validator $validator;

	public function setUp(): void {
		parent::setUp();

		$this->validator = new Validator();
	}

	public function testValidateShareMetadata() {
		$valid = [
			null,
			[
				'version' => '1.0',
			],
			[
				'version' => '1.0',
				'signature_fields' => [],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'id' => 'foo',
						'x' => 0,
						'y' => 0,
						'width' => 100,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'id' => 'foo',
						'x' => 0,
						'y' => 0,
						'width' => 100,
						'height' => 100,
					],
					[
						'page' => 2,
						'id' => 'bar',
						'x' => 1.2,
						'y' => 2.3,
						'width' => 100.4,
						'height' => 100.5,
					],
				],
			],
		];
		$invalid = [
			[
				'version' => '0'
			],
			[
				'signature_fields' => [],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'id' => 'foo',
						'x' => 0,
						'y' => 0,
						'width' => 100,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'x' => 0,
						'y' => 0,
						'width' => 100,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'id' => 'foo',
						'y' => 0,
						'width' => 100,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'id' => 'foo',
						'x' => 0,
						'width' => 100,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'id' => 'foo',
						'x' => 0,
						'y' => 0,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'id' => 'foo',
						'x' => 0,
						'y' => 0,
						'width' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1.1,
						'id' => 'foo',
						'x' => 0,
						'y' => 0,
						'width' => 100,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => '1',
						'id' => 'foo',
						'x' => 0,
						'y' => 0,
						'width' => 100,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'id' => 123,
						'x' => 0,
						'y' => 0,
						'width' => 100,
						'height' => 100,
					],
				],
			],
			[
				'version' => '1.0',
				'signature_fields' => [
					[
						'page' => 1,
						'id' => 'foo',
						'x' => '0',
						'y' => '0',
						'width' => '100',
						'height' => '100',
					],
				],
			],
		];

		foreach ($valid as $md) {
			$this->assertNull($this->validator->validateShareMetadata($md), 'Metadata ' . print_r($md, true) . ' should be valid');
		}
		foreach ($invalid as $md) {
			$this->assertNotNull($this->validator->validateShareMetadata($md), 'Metadata ' . print_r($md, true) . ' should not be valid');
		}
	}

	public function testValidateShareOptions() {
		$valid = [
			null,
			[
				'signed_save_mode' => 'new',
			],
			[
				'signed_save_mode' => 'replace',
			],
			[
				'signed_save_mode' => 'none',
			],
		];
		$invalid = [
			[
				'signed_save_mode' => 'foo',
			],
		];

		foreach ($valid as $opt) {
			$this->assertNull($this->validator->validateShareOptions($opt), 'Options ' . print_r($opt, true) . ' should be valid');
		}
		foreach ($invalid as $opt) {
			$this->assertNotNull($this->validator->validateShareOptions($opt), 'Options ' . print_r($opt, true) . ' should not be valid');
		}
	}
}
