<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Events\SignEvent;
use OCA\Esig\Mails;
use OCA\Esig\Requests;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;

class Manager {

	const ISO8601_EXTENDED = "Y-m-d\TH:i:s.uP";

	private ILogger $logger;
	private IL10N $l10n;
	private IUserManager $userManager;
	private IRootFolder $root;
	private Client $client;
	private Config $config;
	private Requests $requests;
	private Mails $mails;

	public function __construct(ILogger $logger,
								IL10N $l10n,
								IUserManager $userManager,
								IRootFolder $root,
								Client $client,
								Config $config,
								Requests $requests,
								Mails $mails) {
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->root = $root;
		$this->client = $client;
		$this->config = $config;
		$this->requests = $requests;
		$this->mails = $mails;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addServiceListener(SignEvent::class, self::class);
	}

	public function handle(Event $event): void {
		if ($event instanceof SignEvent) {
			if ($event->isLastSignature()) {
				$this->handleLastSignature($event);
				return;
			}
		}
	}

	private function handleLastSignature(SignEvent $event) {
		$request = $event->getRequest();
		$owner = null;
		$file = null;
		foreach ($request['recipients'] as $recipient) {
			if ($recipient['type'] !== 'email') {
				// Regular users get notified through Nextcloud.
				continue;
			}

			if (!$owner) {
				$owner = $this->userManager->get($request['user_id']);
				if (!$owner) {
					// Should not happen, owned requests are deleted when users are.
					return;
				}
			}

			if (!$file) {
				$files = $this->root->getUserFolder($owner->getUID())->getById($request['file_id']);
				if (empty($files)) {
					// Should not happen, requests are deleted when files are.
					return;
				}

				$file = $files[0];
			}

			$this->mails->sendLastSignatureMail($event->getRequestId(), $request, $owner, $file, $recipient);
		}
	}

	private function storeSignedResult(?IUser $user, array $row, string $type, string $value, \DateTime $signed, array $account) {
		$owner = $this->userManager->get($row['user_id']);
		if (!$owner) {
			// Should not happen, owned requests are deleted when users are.
			return;
		}

		$files = $this->root->getUserFolder($owner->getUID())->getById($row['file_id']);
		if (empty($files)) {
			// Should not happen, requests are deleted when files are.
			return;
		}

		$file = $files[0];
		$folder = $file->getParent();

		switch ($type) {
			case 'user':
				$signerName = $user->getDisplayName();
				break;
			case 'email':
				$signerName = $value;
				break;
		}

		$info = pathinfo($row['filename']);
		$filename = $this->l10n->t('%1$s signed by %2$s on %3$s', [
			$info['filename'],
			$signerName,
			$signed->format(self::ISO8601_EXTENDED),
		]) . ($info['extension'] ? ('.' . $info['extension']) : '');

		$data = $this->client->downloadSignedFile($row['esig_file_id'], $account, $row['esig_server']);
		$created = $folder->newFile($filename, $data);
		return $created;
	}

	private function replaceSignedResult(?IUser $user, array $row, string $type, string $value, \DateTime $signed, array $account) {
		$owner = $this->userManager->get($row['user_id']);
		if (!$owner) {
			// Should not happen, owned requests are deleted when users are.
			return;
		}

		$files = $this->root->getUserFolder($owner->getUID())->getById($row['file_id']);
		if (empty($files)) {
			// Should not happen, requests are deleted when files are.
			return;
		}

		/** @var File $file */
		$file = $files[0];

		$data = $this->client->downloadSignedFile($row['esig_file_id'], $account, $row['esig_server']);
		$file->putContent($data);
		return $file;
	}

	public function saveSignedResult(array $request, string $type, string $value, \DateTime $signed, ?IUser $user, array $account) {
		$signed_save_mode = $request['signed_save_mode'];
		if (empty($signed_save_mode)) {
			$signed_save_mode = $this->config->getSignedSaveMode();
		}

		try {
			switch ($signed_save_mode) {
				case Requests::MODE_SIGNED_NEW:
					$this->storeSignedResult($user, $request, $type, $value, $signed, $account);
					break;
				case Requests::MODE_SIGNED_REPLACE:
					$this->replaceSignedResult($user, $request, $type, $value, $signed, $account);
					break;
				case Requests::MODE_SIGNED_NONE:
					break;
			}

			$this->requests->markRequestSavedById($request['id'], $type, $value);
			$this->logger->info('Processed signed result of ' . $type . ' ' . $value . ' for request ' . $request['id'], [
				'app' => Application::APP_ID,
			]);
		} catch (\Exception $e) {
			$this->logger->logException($e, [
				'message' => 'Error processing signed result of ' . $type . ' ' . $value . ' for request ' . $request['id'],
				'app' => Application::APP_ID,
			]);
		}
	}

}
