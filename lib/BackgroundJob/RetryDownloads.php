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

	protected function run($argument): void {
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return;
		}

		$pending = $this->requests->getPendingDownloads();
		foreach ($pending as $request) {
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

			$signed = $request['last_signed'];
			if (is_string($signed)) {
				$signed = $this->requests->parseDateTime($signed);
			}
			$this->manager->saveSignedResult($request, $signed, $user, $account);
		}
	}
}
