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
namespace OCA\Esig;

use DomainException;
use OCA\Esig\Vendor\Firebase\JWT\JWT;
use OCA\Esig\Vendor\Firebase\JWT\Key;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class Tokens {
	private ITimeFactory $timeFactory;
	private IURLGenerator $urlGenerator;
	private LoggerInterface $logger;
	private Config $config;

	public function __construct(ITimeFactory $timeFactory,
		IURLGenerator $urlGenerator,
		LoggerInterface $logger,
		Config $config) {
		$this->timeFactory = $timeFactory;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->config = $config;
	}

	public function getToken(array $account, string $subject, string $action): string {
		$now = $this->timeFactory->getTime();
		$claims = [
			'iss' => $this->urlGenerator->getAbsoluteURL(''),
			'sub' => $subject,
			'iat' => $now,
			'exp' => $now + (5 * 60),
			'act' => $action,
		];
		$jwt = JWT::encode($claims, $account['secret'], 'EdDSA');
		return $jwt;
	}

	public function validateToken(string $token, array $account, string $subject, string $action): bool {
		if (!$token || !$account || !isset($account['secret'])) {
			return false;
		}

		$pubkey = sodium_crypto_sign_publickey_from_secretkey(base64_decode($account['secret']));
		$key = new Key(base64_encode($pubkey), 'EdDSA');
		try {
			$claims = JWT::decode($token, $key);
		} catch (UnexpectedValueException $e) {
			return false;
		} catch (DomainException $e) {
			$this->logger->error('Could not decode token ' . $token, [
				'exception' => $e,
			]);
			return false;
		}

		return ($claims->sub === $subject && $claims->act === $action);
	}
}
