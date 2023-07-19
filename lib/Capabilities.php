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

use OCA\Esig\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\Capabilities\IPublicCapability;
use OCP\IUserSession;

class Capabilities implements IPublicCapability {
	protected Config $config;
	protected IUserSession $userSession;
	private IAppManager $appManager;

	public function __construct(Config $config,
		IUserSession $userSession,
		IAppManager $appManager) {
		$this->config = $config;
		$this->userSession = $userSession;
		$this->appManager = $appManager;
	}

	public function getCapabilities(): array {
		$user = $this->userSession->getUser();

		$account = $this->config->getAccount();
		$capabilities = [
			'features' => [
				'multiple-recipients',
				'sign-anonymous',
				'verify-signatures',
			],
			'config' => [
				'requests' => [
					'signed-save-mode' => $this->config->getSignedSaveMode(),
				],
				'user' => [],
			],
			'version' => $this->appManager->getAppVersion(Application::APP_ID),
			'has-account' => !empty($account['id']) && !empty($account['secret']),
		];

		if ($user) {
			$capabilities['config']['user'] = [
				'has-signature-image' => $this->config->getSignatureImage($user) !== null,
			];
		}

		return [
			'esig' => $capabilities,
		];
	}
}
