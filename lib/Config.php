<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCP\IConfig;

class Config {

	public const DEFAULT_SERVER = "https://api.certificate24.com/";

	private IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getServer(): string {
		$server = $this->config->getAppValue('esig', 'server', self::DEFAULT_SERVER);
		if (empty($server)) {
			$server = self::DEFAULT_SERVER;
		}

		if ($server[strlen($server)-1] != '/') {
			$server = $server . '/';
		}
		return $server;
	}

	public function getAccount(): array {
		$account = $this->config->getAppValue('esig', 'account', '');
		if (!$account) {
			$account = [
				'id' => '',
				'secret' => '',
			];
		} else {
			$account = json_decode($account, true);
		}
		return $account;
	}

}
