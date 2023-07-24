<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, struktur AG.
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
namespace OCA\Certificate24\BackgroundJob;

use OCA\Certificate24\Config;
use OCA\Certificate24\Manager;
use OCA\Certificate24\Requests;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class RetryDownloads extends TimedJob {
	private LoggerInterface $logger;
	private IUserManager $userManager;
	private Config $config;
	private Requests $requests;
	private Manager $manager;

	public function __construct(ITimeFactory $timeFactory,
		LoggerInterface $logger,
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
			if ($account['id'] !== $request['c24_account_id']) {
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
