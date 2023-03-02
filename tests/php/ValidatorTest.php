<?php

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
