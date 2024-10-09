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
namespace OCA\Certificate24;

use OCA\Certificate24\AppInfo\Application;
use OCA\Certificate24\Events\SignEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

class Manager {
	// Maximum length of a filename to support saving on Windows.
	public const MAX_FILENAME_LENGTH = 255;

	private LoggerInterface $logger;
	private IEventDispatcher $dispatcher;
	private IConfig $systemConfig;
	private IL10N $l10n;
	private IFactory $l10nFactory;
	private IDateTimeFormatter $formatter;
	private IUserManager $userManager;
	private IRootFolder $root;
	private Client $client;
	private Config $config;
	private Requests $requests;
	private Mails $mails;

	public function __construct(LoggerInterface $logger,
		IEventDispatcher $dispatcher,
		IConfig $systemConfig,
		IL10N $l10n,
		IFactory $l10nFactory,
		IDateTimeFormatter $formatter,
		IUserManager $userManager,
		IRootFolder $root,
		Client $client,
		Config $config,
		Requests $requests,
		Mails $mails) {
		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
		$this->systemConfig = $systemConfig;
		$this->l10n = $l10n;
		$this->l10nFactory = $l10nFactory;
		$this->formatter = $formatter;
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

	public static function safeFilename(string $filename): string {
		$filename = str_replace(':', '.', $filename);
		$filename = str_replace('\\', '_', $filename);
		return $filename;
	}

	private function storeSignedResult(?IUser $user, array $row, \DateTime $signed, array $account) {
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

		$lang = $this->l10nFactory->getUserLanguage($owner);
		$timeZone = $this->systemConfig->getUserValue($owner->getUID(), 'core', 'timezone', null);
		if ($timeZone) {
			$timeZone = new \DateTimeZone($timeZone);
		} else {
			$timeZone = null;
		}

		if ($lang) {
			$l10n = $this->l10nFactory->get(Application::APP_ID, $lang);
			if (!$l10n) {
				$l10n = $this->l10n;
			}
		} else {
			$l10n = $this->l10n;
		}
		$file = $files[0];
		$folder = $file->getParent();

		switch ($row['recipient_type']) {
			case 'user':
				$signerName = $user ? $user->getDisplayName() : $row['recipient'];
				break;
			case 'email':
				$signerName = $row['recipient'];
				break;
		}

		$info = pathinfo($row['filename']);
		if (count($row['recipients']) === 1) {
			$filename = $l10n->t('%1$s signed by %2$s on %3$s', [
				$info['filename'],
				$signerName,
				$this->formatter->formatDateTime($signed, 'long', 'medium', $timeZone, $l10n),
			]) . ($info['extension'] ? ('.' . $info['extension']) : '');
			if (strlen($filename) >= self::MAX_FILENAME_LENGTH) {
				$filename = $l10n->t('%1$s signed on %2$s', [
					$info['filename'],
					$this->formatter->formatDateTime($signed, 'long', 'medium', $timeZone, $l10n),
				]) . ($info['extension'] ? ('.' . $info['extension']) : '');
			}
		} else {
			$filename = $l10n->t('%1$s signed on %2$s', [
				$info['filename'],
				$this->formatter->formatDateTime($signed, 'long', 'medium', $timeZone, $l10n),
			]) . ($info['extension'] ? ('.' . $info['extension']) : '');
		}

		$data = $this->client->downloadSignedFile($row['c24_file_id'], $account, $row['c24_server']);
		$filename = $this->safeFilename($filename);
		$created = $folder->newFile($filename, $data);
		return $created;
	}

	private function replaceSignedResult(?IUser $user, array $row, \DateTime $signed, array $account) {
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

		$data = $this->client->downloadSignedFile($row['c24_file_id'], $account, $row['c24_server']);
		$file->putContent($data);
		return $file;
	}

	public function saveSignedResult(array $request, \DateTime $signed, ?IUser $user, array $account) {
		$signed_save_mode = $request['signed_save_mode'];
		if (empty($signed_save_mode)) {
			$signed_save_mode = $this->config->getSignedSaveMode();
		}

		try {
			switch ($signed_save_mode) {
				case Requests::MODE_SIGNED_NEW:
					$this->storeSignedResult($user, $request, $signed, $account);
					break;
				case Requests::MODE_SIGNED_REPLACE:
					$this->replaceSignedResult($user, $request, $signed, $account);
					break;
				case Requests::MODE_SIGNED_NONE:
					break;
			}

			$this->requests->markRequestSavedById($request['id']);
			$this->logger->info('Processed signed result of request ' . $request['id']);
		} catch (\Exception $e) {
			$this->logger->error('Error processing signed result of request ' . $request['id'], [
				'exception' => $e,
			]);
		}
	}

	public function processSignatureDetails(array $request, array $account, string $type, string $value, array $details) {
		$signed = $this->requests->parseDateTime($details['signed'] ?? null);
		if (!$signed) {
			return;
		}

		$isLast = $this->requests->markRequestSigned($request, $type, $value, $signed, null);
		$this->logger->info('Request ' . $request['id'] . ' was signed by ' . $type . ' ' . $value . ' on ' . $signed->format(Requests::ISO8601_EXTENDED));

		if ($isLast) {
			$this->saveSignedResult($request, $signed, null, $account);
		}
	}

	public function deleteRequest(array $request, array $account) {
		if ($account['id'] !== $request['c24_account_id']) {
			$this->logger->error('Request ' . $request['id'] . ' of user ' . $request['user_id'] . ' is from a different account, got ' . $account['id']);
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		try {
			$data = $this->client->deleteFile($request['c24_file_id'], $account, $request['c24_server']);
		} catch (\Exception $e) {
			$this->logger->error('Error deleting request ' . $request['id'] . ' of user ' . $request['user_id'], [
				'exception' => $e,
			]);
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		$status = $data['status'] ?? '';
		if ($status !== 'success') {
			$this->logger->error('Error deleting request ' . $request['id'] . ' of user ' . $request['user_id'] . ': ' . print_r($data, true));
			// TODO: Add cronjob to delete in the background.
			$this->requests->markRequestDeletedById($request['id']);
			return;
		}

		$this->logger->info('Deleted request ' . $request['id'] . ' of user ' . $request['user_id']);
		$this->requests->deleteRequestById($request['id']);
	}

}
