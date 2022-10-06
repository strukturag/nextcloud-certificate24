<?php

declare(strict_types=1);

namespace OCA\Esig\Controller;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Config;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {

	public function __construct(string $appName,
								IRequest $request,
								Config $config) {
		parent::__construct($appName, $request);
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
		$response = new TemplateResponse('esig', 'index', [
			'app' => Application::APP_ID,
			'id-app-content' => '#app-content-vue',
			'id-app-navigation' => '#app-navigation-vue',
		]);

		$server = $this->config->getServer();
		if ($server) {
			$csp = new ContentSecurityPolicy();
			$csp->addAllowedConnectDomain($server);
			$response->setContentSecurityPolicy($csp);
		}
		return $response;
	}

}
