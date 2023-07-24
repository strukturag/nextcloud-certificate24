<?php

declare(strict_types=1);

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
namespace OCA\Certificate24;

use OCA\Certificate24\Vendor\Opis\JsonSchema\Errors\ErrorFormatter;
use OCA\Certificate24\Vendor\Opis\JsonSchema\Helper;
use OCA\Certificate24\Vendor\Opis\JsonSchema\Validator as JsonValidator;

class Validator {
	private function validateWithSchema(string $filename, ?array $data): ?array {
		if (!$data) {
			return null;
		}

		$schema = file_get_contents(__DIR__ . '/../schema/' . $filename);
		if (!$schema) {
			return null;
		}

		$validator = new JsonValidator();
		$result = $validator->validate(Helper::toJSON($data), $schema);
		if ($result->isValid()) {
			return null;
		}

		$error = $result->error();
		$formatter = new ErrorFormatter();
		return $formatter->format($error, false);
	}

	public function validateShareMetadata(?array $metadata): ?array {
		return $this->validateWithSchema('request_metadata.json', $metadata);
	}

	public function validateShareOptions(?array $options): ?array {
		return $this->validateWithSchema('request_options.json', $options);
	}
}
