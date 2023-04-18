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
namespace OCA\Esig;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IUser;

class Config {
	public const DEFAULT_SERVER = "https://api.certificate24.com/";
	public const DEFAULT_SAVE_MODE = 'new';

	private IConfig $config;
	protected IAppData $appData;

	public function __construct(IConfig $config,
		IAppData $appData) {
		$this->config = $config;
		$this->appData = $appData;
	}

	public function getServer(): string {
		$server = $this->config->getAppValue('esig', 'server', self::DEFAULT_SERVER);
		if (empty($server)) {
			$server = self::DEFAULT_SERVER;
		}

		if ($server[strlen($server) - 1] != '/') {
			$server = $server . '/';
		}
		return $server;
	}

	public function getAccount(): array {
		$account = $this->config->getAppValue('esig', 'account', '');
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
		return $this->config->getAppValue('esig', 'signed_save_mode', self::DEFAULT_SAVE_MODE);
	}

	public function isIntranetInstance(): bool {
		return $this->config->getAppValue('esig', 'intranet_instance', 'false') === 'true';
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
		$value = $this->config->getAppValue('esig', 'delete_max_age', '30');
		if ($value === null || $value === '') {
			$value = '30';
		}

		return (int) $value;
	}

}
