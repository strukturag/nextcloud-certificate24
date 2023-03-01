<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCP\Capabilities\IPublicCapability;
use OCP\IUserSession;
use OCP\App\IAppManager;

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

		$capabilities = [
			'features' => [
				'multiple-recipients',
				'sign-anonymous',
			],
			'config' => [
				'requests' => [
					'signed-save-mode' => $this->config->getSignedSaveMode(),
				],
				'user' => [],
			],
			'version' => $this->appManager->getAppVersion('esig'),
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
