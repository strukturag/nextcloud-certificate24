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
namespace OCA\Esig\BackgroundJob;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Config;
use OCA\Esig\Manager;
use OCA\Esig\Requests;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class DeleteCompleted extends TimedJob {
	private LoggerInterface $logger;
	private Config $config;
	private Requests $requests;
	private Manager $manager;

	public function __construct(ITimeFactory $timeFactory,
		LoggerInterface $logger,
		Config $config,
		Requests $requests,
		Manager $manager) {
		parent::__construct($timeFactory);

		// Every 15 minutes
		$this->setInterval(60 * 1);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);

		$this->logger = $logger;
		$this->config = $config;
		$this->requests = $requests;
		$this->manager = $manager;
	}

	protected function run($argument): void {
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return;
		}

		$maxAgeDays = $this->config->getDeleteMaxAge();
		if ($maxAgeDays <= 0) {
			return;
		}

		$maxAge = new \DateTime();
		$maxAge = $maxAge->sub(new \DateInterval('P' . $maxAgeDays . 'D'));
		$completed = $this->requests->getCompletedRequests($maxAge);

		foreach ($completed as $request) {
			$this->logger->info('Request ' . $request['id'] . ' of user ' . $request['user_id'] . ' is completed for ' . $maxAgeDays . ' days, deleting', [
				'app' => Application::APP_ID,
			]);

			$this->manager->deleteRequest($request, $account);
		}
	}

}
