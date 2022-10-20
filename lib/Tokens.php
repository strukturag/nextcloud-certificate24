<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Esig\Config;
use OCA\Esig\Vendor\Firebase\JWT\JWT;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IURLGenerator;

class Tokens {

	private ITimeFactory $timeFactory;
	private IURLGenerator $urlGenerator;
	private Config $config;

	public function __construct(ITimeFactory $timeFactory,
								IURLGenerator $urlGenerator,
								Config $config) {
		$this->timeFactory = $timeFactory;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
	}

  public function getToken(array $account, string $subject): string {
		$now = $this->timeFactory->getTime();
		$claims = [
			'iss' => $this->urlGenerator->getAbsoluteURL(''),
			'sub' => $subject,
			'iat' => $now,
			'exp' => $now + (5 * 60),
		];
		$jwt = JWT::encode($claims, $account['secret'], 'EdDSA');
    return $jwt;
  }

}
