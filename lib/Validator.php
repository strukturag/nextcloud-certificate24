<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Esig\Vendor\Opis\JsonSchema\Errors\ErrorFormatter;
use OCA\Esig\Vendor\Opis\JsonSchema\Helper;
use OCA\Esig\Vendor\Opis\JsonSchema\Validator as JsonValidator;

class Validator {

	public function validateShareMetadata(?array $metadata): ?array {
		if (!$metadata) {
			return null;
		}

		$schema = file_get_contents(__DIR__ . '/../schema/request_metadata.json');
		if (!$schema) {
			return null;
		}

		$validator = new JsonValidator();
		$result = $validator->validate(Helper::toJSON($metadata), $schema);
		if ($result->isValid()) {
			return null;
		}

		$error = $result->error();
		$formatter = new ErrorFormatter();
		return $formatter->format($error, false);
	}

}
