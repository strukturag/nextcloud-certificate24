<?php

declare(strict_types=1);

namespace OCA\Esig;

use DomainException;
use OCA\Esig\AppInfo\Application;
use OCA\Esig\Config;
use OCA\Esig\Vendor\Firebase\JWT\JWT;
use OCA\Esig\Vendor\Firebase\JWT\Key;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ILogger;
use OCP\IURLGenerator;
use UnexpectedValueException;

class Tokens {

	private ITimeFactory $timeFactory;
	private IURLGenerator $urlGenerator;
	private ILogger $logger;
	private Config $config;

	public function __construct(ITimeFactory $timeFactory,
								IURLGenerator $urlGenerator,
								ILogger $logger,
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
			$this->logger->logException($e, [
				'message' => 'Could not decode token ' . $token,
				'app' => Application::APP_ID,
			]);
			return false;
		}

		return ($claims->sub === $subject && $claims->act === $action);
	}

}
