<?php

declare(strict_types=1);

namespace OCA\Esig\BackgroundJob;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Config;
use OCA\Esig\Manager;
use OCA\Esig\Requests;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\BackgroundJob\IJob;
use OCP\ILogger;
use OCP\IUserManager;

class RetryDownloads extends TimedJob {

	const ISO8601_EXTENDED = "Y-m-d\TH:i:s.uP";

	private ILogger $logger;
	private IUserManager $userManager;
	private Config $config;
	private Requests $requests;
	private Manager $manager;

	public function __construct(ITimeFactory $timeFactory,
								ILogger $logger,
								IUserManager $userManager,
								Config $config,
								Requests $requests,
								Manager $manager) {
		parent::__construct($timeFactory);

		// Every 5 minutes
		$this->setInterval(60 * 5);
		$this->setTimeSensitivity(IJob::TIME_SENSITIVE);

		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->config = $config;
		$this->requests = $requests;
		$this->manager = $manager;
	}

	private function parseDateTime($s) {
		if (!$s) {
			return null;
		}
		if ($s[strlen($s) - 1] === 'Z') {
			$s = substr($s, 0, strlen($s) - 1) . '+00:00';
		}
		if ($s[strlen($s) - 3] !== ':') {
			$s = $s . ':00';
		}
		if ($s[10] === ' ') {
			$s[10] = 'T';
		}
		if (strlen($s) === 19) {
			// SQLite backend stores without timezone, e.g. "2022-10-12 06:54:54".
			$s .= '+00:00';
		}
		$dt = \DateTime::createFromFormat(\DateTime::ISO8601, $s);
		if (!$dt) {
			$dt = \DateTime::createFromFormat(self::ISO8601_EXTENDED, $s);
		}
		if (!$dt) {
			$this->logger->error('Could not convert ' . $s . ' to datetime', [
				'app' => Application::APP_ID,
			]);
			$dt = null;
		}
		return $dt;
	}

	protected function run($argument): void {
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return;
		}

		$pending = $this->requests->getPendingDownloads();
		foreach ($pending['single'] as $request) {
			if ($account['id'] !== $request['esig_account_id']) {
				continue;
			}

			$user = null;
			if ($request['recipient_type'] === 'user') {
				$user = $this->userManager->get($request['recipient']);
				if (!$user) {
					// Should not happen, requests will get deleted if the recipient is deleted.
					continue;
				}
			}

			$signed = $request['signed'];
			if (is_string($signed)) {
				$signed = $this->parseDateTime($signed);
			}
			$this->manager->saveSignedResult($request, $request['recipient_type'], $request['recipient'], $signed, $user, $account);
		}

		foreach ($pending['multi'] as $entry) {
			$request = $entry['request'];
			if ($account['id'] !== $request['esig_account_id']) {
				continue;
			}

			$user = null;
			if ($entry['type'] === 'user') {
				$user = $this->userManager->get($entry['value']);
				if (!$user) {
					// Should not happen, requests will get deleted if the recipient is deleted.
					continue;
				}
			}

			$signed = $entry['signed'];
			if (is_string($signed)) {
				$signed = $this->parseDateTime($signed);
			}
			$this->manager->saveSignedResult($request, $entry['type'], $entry['value'], $signed, $user, $account);
		}
	}
}
