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
namespace OCA\Certificate24\Settings;

use OCA\Certificate24\Config;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;

class PersonalSettings implements ISettings {
	private Config $config;
	private IInitialState $initialState;
	private ?IUser $currentUser = null;
	private IURLGenerator $urlGenerator;

	public function __construct(Config $config,
		IInitialState $initialState,
		IUserSession $userSession,
		IURLGenerator $urlGenerator) {
		$this->config = $config;
		$this->initialState = $initialState;
		$this->currentUser = $userSession->getUser();
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$settings = [
			'signature-image-url' => $this->urlGenerator->linkToRouteAbsolute(Config::APP_ID . '.Download.downloadSignatureImage'),
		];
		if ($this->config->getSignatureImage($this->currentUser)) {
			$settings['has-signature-image'] = true;
		}
		$this->initialState->provideInitialState('settings', $settings);

		$server = $this->config->getApiServer();
		if (!empty($server)) {
			$this->initialState->provideInitialState(
				'vinegar_server',
				$server
			);
		}

		Util::addScript(Config::APP_ID, Config::APP_ID . '-personal-settings');

		return new TemplateResponse(Config::APP_ID, 'settings/personal-settings', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return Config::APP_ID;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 0;
	}
}
