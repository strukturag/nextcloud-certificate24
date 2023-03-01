<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Esig\Vendor\Opis\JsonSchema\Errors\ErrorFormatter;
use OCA\Esig\Vendor\Opis\JsonSchema\Helper;
use OCA\Esig\Vendor\Opis\JsonSchema\Validator as JsonValidator;

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
