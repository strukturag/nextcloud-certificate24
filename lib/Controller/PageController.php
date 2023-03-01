<?php

declare(strict_types=1);

namespace OCA\Esig\Controller;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Config;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\HintException;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {

	private IInitialState $initialState;
	private IUserSession $userSession;
	private Config $config;

	public function __construct(string $appName,
								IRequest $request,
								IInitialState $initialState,
								IUserSession $userSession,
								Config $config) {
		parent::__construct($appName, $request);
		$this->initialState = $initialState;
		$this->userSession = $userSession;
		$this->config = $config;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 * @throws HintException
	 */
	public function index(): Response {
		$server = $this->config->getServer();
		if (!empty($server)) {
			$this->initialState->provideInitialState(
				'vinegar_server',
				$server
			);
		}

		$userSettings = [];
		$user = $this->userSession->getUser();
		if ($user) {
			if ($this->config->getSignatureImage($user)) {
				$userSettings['has-signature-image'] = true;
			}
		}
		$this->initialState->provideInitialState(
			'user-settings',
			$userSettings
		);

		$response = new TemplateResponse('esig', 'index', [
			'app' => Application::APP_ID,
			'id-app-content' => '#app-content-vue',
			'id-app-navigation' => '#app-navigation-vue',
		]);
		return $response;
	}

		/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 * @throws HintException
	 */
	public function sign(string $id): Response {
		$server = $this->config->getServer();
		if (!empty($server)) {
			$this->initialState->provideInitialState(
				'vinegar_server',
				$server
			);
		}

		$response = new TemplateResponse('esig', 'sign', [
			'app' => Application::APP_ID,
		], 'blank');
		return $response;
	}

}
