<?php

declare(strict_types=1);

namespace OCA\Esig\Settings\Admin;

use OCA\Esig\Config;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
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

	public function __construct(Config $config,
								IInitialState $initialState,
								IUserSession $userSession,
								IL10N $l10n,
								IFactory $l10nFactory) {
		$this->config = $config;
		$this->initialState = $initialState;
		$this->currentUser = $userSession->getUser();
		$this->l10n = $l10n;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$account = $this->config->getAccount();
		$server = $this->config->getServer();
		$account['server'] = $server;
		$this->initialState->provideInitialState('account', $account);

		Util::addScript('esig', 'esig-admin-settings');

		return new TemplateResponse('esig', 'settings/admin-settings', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'esig';
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
