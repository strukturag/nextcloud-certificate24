<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Requests;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;
use OCP\ILogger;
use OCP\IUser;

class DeleteListener implements IEventListener {

	protected ILogger $logger;
	protected Requests $requests;
	protected Client $client;
	protected Config $config;

	public function __construct(ILogger $logger,
								Requests $requests,
								Client $client,
								Config $config) {
		$this->logger = $logger;
		$this->requests = $requests;
		$this->client = $client;
		$this->config = $config;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addServiceListener(UserDeletedEvent::class, self::class);
	}

	private function deleteRequest(array $account, array $request): void {
		if ($account['id'] !== $request['esig_account_id']) {
			$this->logger->error('Request ' . $request['id'] . ' of user ' . $request['user_id'] . ' is from a different account, got ' . $account['id'], [
				'app' => Application::APP_ID,
			]);
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		try {
			$data = $this->client->deleteFile($request['esig_file_id'], $account, $request['esig_server']);
		} catch (\Exception $e) {
			$this->logger->logException($e, [
				'app' => Application::APP_ID,
				'message' => 'Error deleting request ' . $request['id'] . ' of user ' . $request['user_id'],
			]);
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		$status = $data['status'] ?? '';
		if ($status !== 'success') {
			$this->logger->error('Error deleting request ' . $request['id'] . ' of user ' . $request['user_id'] . ': ' . print_r($data, true), [
				'app' => Application::APP_ID,
			]);
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		$this->logger->info('Deleted request ' . $request['id'] . ' of user ' . $request['user_id'], [
			'app' => Application::APP_ID,
		]);
		$this->requests->deleteRequestById($request['id']);
	}

	public function handle(Event $event): void {
		if ($event instanceof UserDeletedEvent) {
			$user = $event->getUser();
			$account = $this->config->getAccount();
			$requests = $this->requests->getOwnRequests($user, true);
			foreach ($requests as $request) {
				$this->deleteRequest($account, $request);
			}

			$requests = $this->requests->getIncomingRequests($user, true);
			foreach ($requests as $request) {
				$this->deleteRequest($account, $request);
			}
		}
	}

}
