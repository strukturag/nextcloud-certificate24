<?php

declare(strict_types=1);

namespace OCA\Esig\BackgroundJob;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use OCA\Esig\AppInfo\Application;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Manager;
use OCA\Esig\Requests;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\BackgroundJob\IJob;
use OCP\ILogger;

class FetchSigned extends TimedJob {

	private ILogger $logger;
	private Config $config;
	private Requests $requests;
	private Client $client;
	private Manager $manager;

	public function __construct(ITimeFactory $timeFactory,
								ILogger $logger,
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
			$this->logger->logException($e, [
				'message' => 'Error connecting to ' . $server,
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

			$this->logger->logException($e, [
				'message' => 'Error sending request to ' . $server . ': ' . print_r($body, true),
				'app' => Application::APP_ID,
			]);
			return null;
		} catch (\Exception $e) {
			$this->logger->logException($e, [
				'message' => 'Error sending request to ' . $server,
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
