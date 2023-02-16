<?php

declare(strict_types=1);

namespace OCA\Esig\BackgroundJob;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use OCA\Esig\AppInfo\Application;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Events\SignEvent;
use OCA\Esig\Manager;
use OCA\Esig\Requests;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\BackgroundJob\IJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;

class FetchSigned extends TimedJob {

	const ISO8601_EXTENDED = "Y-m-d\TH:i:s.uP";

	private ILogger $logger;
	private IEventDispatcher $dispatcher;
	private Config $config;
	private Requests $requests;
	private Client $client;
	private Manager $manager;

	public function __construct(ITimeFactory $timeFactory,
								ILogger $logger,
								IEventDispatcher $dispatcher,
								Config $config,
								Requests $requests,
								Client $client,
								Manager $manager) {
		parent::__construct($timeFactory);

		// Every 5 minutes
		$this->setInterval(60 * 5);
		$this->setTimeSensitivity(IJob::TIME_SENSITIVE);

		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
		$this->config = $config;
		$this->requests = $requests;
		$this->client = $client;
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

			$signed = $this->parseDateTime($details['signed'] ?? null);
			if ($signed) {
				$isLast = $this->requests->markRequestSignedById($request['id'], $request['recipient_type'], $request['recipient'], $signed);
				$this->logger->info('Request ' . $request['id'] . ' was signed by ' . $request['recipient_type'] . ' ' . $request['recipient'] . ' on ' . $signed->format(self::ISO8601_EXTENDED), [
					'app' => Application::APP_ID,
				]);

				$event = new SignEvent($request['id'], $request, $request['recipient_type'], $request['recipient'], $signed, null, $isLast);
				$this->dispatcher->dispatch(SignEvent::class, $event);

				$this->manager->saveSignedResult($request, $request['recipient_type'], $request['recipient'], $signed, null, $account);
			}
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

			$signed = $this->parseDateTime($details['signed'] ?? null);
			if ($signed) {
				$isLast = $this->requests->markRequestSignedById($request['id'], $entry['type'], $entry['value'], $signed);
				$this->logger->info('Request ' . $request['id'] . ' was signed by ' . $entry['type'] . ' ' . $entry['value'] . ' on ' . $signed->format(self::ISO8601_EXTENDED), [
					'app' => Application::APP_ID,
				]);

				$event = new SignEvent($request['id'], $request, $entry['type'], $entry['value'], $signed, null, $isLast);
				$this->dispatcher->dispatch(SignEvent::class, $event);

				$this->manager->saveSignedResult($request, $entry['type'], $entry['value'], $signed, null, $account);
			}
		}
	}
}
