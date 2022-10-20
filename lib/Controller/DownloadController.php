<?php

declare(strict_types=1);

namespace OCA\Esig\Controller;

use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Requests;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Util;

class DownloadController extends Controller {

	public function __construct(string $appName,
								IRequest $request,
								IUserSession $userSession,
								IURLGenerator $urlGenerator,
								Client $client,
								Config $config,
								Requests $requests) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->client = $client;
		$this->config = $config;
		$this->requests = $requests;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function downloadOriginal(string $id): Response {
		$req = $this->requests->getRequestById($id);
		if (!$req) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} else if ($account['id'] !== $req['esig_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$user = $this->userSession->getUser();
		if (!$this->requests->mayAccess($user, $req)) {
			$redirectUrl = $this->urlGenerator->linkToRoute('esig.Download.downloadOriginal', ['id' => $id]);
			$response = new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', [
				'redirect_url' => $redirectUrl,
			]));
			$response->throttle();
			return $response;
		}

		$url = $this->client->getOriginalUrl($req['esig_file_id'], $account, $req['esig_server']);
		$url .= (strpos($url, '?') === false) ? '?' : '&';
		$url .= 'download=1';
		return new RedirectResponse($url);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function downloadSigned(string $id): Response {
		$req = $this->requests->getRequestById($id);
		if (!$req) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} else if ($account['id'] !== $req['esig_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$user = $this->userSession->getUser();
		if (!$this->requests->mayAccess($user, $req)) {
			$redirectUrl = $this->urlGenerator->linkToRoute('esig.Download.downloadSigned', ['id' => $id]);
			$response = new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', [
				'redirect_url' => $redirectUrl,
			]));
			$response->throttle();
			return $response;
		}

		$url = $this->client->getSignedUrl($req['esig_file_id'], $account, $req['esig_server']);
		$url .= (strpos($url, '?') === false) ? '?' : '&';
		$url .= 'download=1';
		return new RedirectResponse($url);
	}

}
