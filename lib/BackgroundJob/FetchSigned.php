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

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use OCA\Esig\AppInfo\Application;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Manager;
use OCA\Esig\Requests;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class FetchSigned extends TimedJob {
	private LoggerInterface $logger;
	private Config $config;
	private Requests $requests;
	private Client $client;
	private Manager $manager;

	public function __construct(ITimeFactory $timeFactory,
		LoggerInterface $logger,
		Config $config,
		Requests $requests,
		Client $client,
		Manager $manager) {
		parent::__construct($timeFactory);

		// Every 5 minutes
		$this->setInterval(60 * 5);
		$this->setTimeSensitivity(IJob::TIME_SENSITIVE);

		$this->logger = $logger;
		$this->config = $config;
		$this->requests = $requests;
		$this->client = $client;
		$this->manager = $manager;
	}

	protected function getSignatureDetails(string $id, string $file_id, array $account, string $server, string $signature_id, string $type, string $value): ?array {
		try {
			$details = $this->client->getSignatureDetails($file_id, $account, $server, $signature_id);
		} catch (ConnectException $e) {
			$this->logger->error('Error connecting to ' . $server, [
				'exception' => $e,
				'app' => Application::APP_ID,
			]);
			return null;
		} catch (RequestException $e) {
			$response = $e->getResponse();
			$body = null;
			if ($response) {
				$body = $response->getBody()->getContents();
				$decoded = json_decode($body, true);
				if ($decoded) {
					if ($decoded['code'] === 'not_signed') {
						$this->logger->info('Request ' . $id . ' has not been signed by ' . $type . ' ' . $value, [
							'app' => Application::APP_ID,
						]);
						return null;
					}
				}
			}

			$this->logger->error('Error sending request to ' . $server . ': ' . print_r($body, true), [
				'exception' => $e,
				'app' => Application::APP_ID,
			]);
			return null;
		} catch (\Exception $e) {
			$this->logger->error('Error sending request to ' . $server, [
				'exception' => $e,
				'app' => Application::APP_ID,
			]);
			return null;
		}

		$response = json_decode($details, true);
		if (!$response) {
			$this->logger->error('Error decoding response ' . $details . ' from ' . $server, [
				'app' => Application::APP_ID,
			]);
			return null;
		}
		return $response;
	}

	protected function run($argument): void {
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return;
		}

		$pending = $this->requests->getPendingSignatures();
		foreach ($pending['single'] as $request) {
			if ($account['id'] !== $request['esig_account_id']) {
				continue;
			}

			$signature_id = $request['esig_signature_id'] ?? null;
			if (!$signature_id) {
				$this->logger->error('No signature id found for request ' . $request['id'] . ' to fetch signature details for ' . $request['recipient_type'] . ' ' . $request['recipient'], [
					'app' => Application::APP_ID,
				]);
				continue;
			}

			$details = $this->getSignatureDetails($request['id'], $request['esig_file_id'], $account, $request['esig_server'], $signature_id, $request['recipient_type'], $request['recipient']);
			if (!$details) {
				continue;
			}

			$this->manager->processSignatureDetails($request, $account, $request['recipient_type'], $request['recipient'], $details);
		}

		foreach ($pending['multi'] as $entry) {
			$request = $entry['request'];
			if ($account['id'] !== $request['esig_account_id']) {
				continue;
			}

			$signature_id = $entry['esig_signature_id'] ?? null;
			if (!$signature_id) {
				$this->logger->error('No signature id found for request ' . $request['id'] . ' to fetch signature details for ' . $entry['type'] . ' ' . $entry['value'], [
					'app' => Application::APP_ID,
				]);
				continue;
			}

			$details = $this->getSignatureDetails($request['id'], $request['esig_file_id'], $account, $request['esig_server'], $signature_id, $entry['type'], $entry['value']);
			if (!$details) {
				continue;
			}

			$this->manager->processSignatureDetails($request, $account, $entry['type'], $entry['value'], $details);
		}
	}
}
