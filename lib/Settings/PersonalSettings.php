<?php

declare(strict_types=1);

namespace OCA\Esig\Settings;

use OCA\Esig\Config;
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
			'signature-image-url' => $this->urlGenerator->linkToRouteAbsolute('esig.Download.downloadSignatureImage'),
		];
		if ($this->config->getSignatureImage($this->currentUser)) {
			$settings['has-signature-image'] = true;
		}
		$this->initialState->provideInitialState('settings', $settings);

		$server = $this->config->getServer();
		if (!empty($server)) {
			$this->initialState->provideInitialState(
				'vinegar_server',
				$server
			);
		}

		Util::addScript('esig', 'esig-personal-settings');

		return new TemplateResponse('esig', 'settings/personal-settings', [], '');
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
