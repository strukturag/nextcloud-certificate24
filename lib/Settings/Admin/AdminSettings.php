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
namespace OCA\Certificate24\Settings\Admin;

use OCA\Certificate24\Config;
use OCA\Certificate24\Verify;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings {
	private Config $config;
	private IInitialState $initialState;
	private ?IUser $currentUser = null;
	private IL10N $l10n;
	private IFactory $l10nFactory;
	private IURLGenerator $urlGenerator;
	private Verify $verify;

	public function __construct(Config $config,
		IInitialState $initialState,
		IUserSession $userSession,
		IL10N $l10n,
		IFactory $l10nFactory,
		IURLGenerator $urlGenerator,
		Verify $verify) {
		$this->config = $config;
		$this->initialState = $initialState;
		$this->currentUser = $userSession->getUser();
		$this->l10n = $l10n;
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
		$this->verify = $verify;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$account = $this->config->getAccount();
		$apiServer = $this->config->getApiServer();
		$webServer = $this->config->getWebServer();
		$account['api_server'] = $apiServer;
		$account['web_server'] = $webServer;
		$this->initialState->provideInitialState('account', $account);
		$this->initialState->provideInitialState('nextcloud', [
			'url' => $this->urlGenerator->getAbsoluteURL(''),
		]);
		$last = $this->verify->getLastVerified();
		$this->initialState->provideInitialState('settings', [
			'signed_save_mode' => $this->config->getSignedSaveMode(),
			'insecure_skip_verify' => $this->config->insecureSkipVerify(),
			'background_verify' => $this->config->isBackgroundVerifyEnabled(),
			'delete_max_age' => $this->config->getDeleteMaxAge(),
			'last_verified' => $last ? $last->format(\DateTime::ATOM) : null,
			'unverified_count' => $this->verify->getUnverifiedCount(),
		]);

		Util::addScript(Config::APP_ID, Config::APP_ID . '-admin-settings');

		return new TemplateResponse(Config::APP_ID, 'settings/admin-settings', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'certificate24';
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
