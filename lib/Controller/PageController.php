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
namespace OCA\Esig\Controller;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Config;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\HintException;
use OCP\IRequest;
use OCP\IUserSession;

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
