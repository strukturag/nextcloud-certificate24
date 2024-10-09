<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, struktur AG.
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

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IUser;

class Config {
	public const APP_ID = 'certificate24';

	public const DEFAULT_API_SERVER = 'https://api.certificate24.com/';
	public const DEFAULT_WEB_SERVER = 'https://www.certificate24.com/';
	public const DEFAULT_REQUEST_TIMEOUT = '300';
	public const DEFAULT_SAVE_MODE = 'new';

	private IConfig $config;
	protected IAppData $appData;

	public function __construct(IConfig $config,
		IAppData $appData) {
		$this->config = $config;
		$this->appData = $appData;
	}

	public function getApiServer(): string {
		$server = $this->config->getAppValue(self::APP_ID, 'api_server', self::DEFAULT_API_SERVER);
		if (empty($server)) {
			$server = self::DEFAULT_API_SERVER;
		}

		if ($server[strlen($server) - 1] != '/') {
			$server = $server . '/';
		}
		return $server;
	}

	public function getWebServer(): string {
		$server = $this->config->getAppValue(self::APP_ID, 'web_server', self::DEFAULT_WEB_SERVER);
		if (empty($server)) {
			$server = self::DEFAULT_WEB_SERVER;
		}

		if ($server[strlen($server) - 1] != '/') {
			$server = $server . '/';
		}
		return $server;
	}

	public function getRequestTimeout(): int {
		$timeout = $this->config->getAppValue(self::APP_ID, 'timeout', self::DEFAULT_REQUEST_TIMEOUT);
		if (empty($timeout)) {
			$timeout = self::DEFAULT_REQUEST_TIMEOUT;
		}

		return (int)$timeout;
	}

	public function getAccount(): array {
		$account = $this->config->getAppValue(self::APP_ID, 'account', '');
		if (!$account) {
			$account = [
				'id' => '',
				'secret' => '',
			];
		} else {
			$account = json_decode($account, true);
		}
		return $account;
	}

	public function getSignedSaveMode(): string {
		return $this->config->getAppValue(self::APP_ID, 'signed_save_mode', self::DEFAULT_SAVE_MODE);
	}

	public function insecureSkipVerify(): bool {
		return $this->config->getAppValue(self::APP_ID, 'insecure_skip_verify', 'false') === 'true';
	}

	public function isBackgroundVerifyEnabled(): bool {
		return $this->config->getAppValue(self::APP_ID, 'background_verify', 'true') === 'true';
	}

	public function sendReminderMails(): bool {
		return $this->config->getAppValue(self::APP_ID, 'send_reminder_mails', 'true') === 'true';
	}

	public function getSignatureImage(IUser $user): ?ISimpleFile {
		try {
			$folder = $this->appData->getFolder($user->getUID());
			return $folder->getFile('signature-image');
		} catch (NotFoundException $e) {
			return null;
		}
	}

	public function deleteSignatureImage(IUser $user) {
		try {
			$folder = $this->appData->getFolder($user->getUID());
			$file = $folder->getFile('signature-image');
		} catch (NotFoundException $e) {
			return;
		}

		try {
			$file->delete();
		} catch (NotPermittedException $e) {
			return null;
		}
	}

	public function storeSignatureImage(IUser $user, string $data): ?ISimpleFile {
		try {
			$folder = $this->appData->getFolder($user->getUID());
		} catch (NotFoundException $e) {
			try {
				$folder = $this->appData->newFolder($user->getUID());
			} catch (NotPermittedException $e) {
				return null;
			}
		}

		try {
			try {
				$file = $folder->getFile('signature-image');
				$file->putContent($data);
				return $file;
			} catch (NotFoundException $e) {
				return $folder->newFile('signature-image', $data);
			}
		} catch (NotPermittedException $e) {
			return null;
		}
	}

	public function getDeleteMaxAge(): int {
		$value = $this->config->getAppValue(self::APP_ID, 'delete_max_age', '30');
		if ($value === null || $value === '') {
			$value = '30';
		}

		return (int)$value;
	}

}
