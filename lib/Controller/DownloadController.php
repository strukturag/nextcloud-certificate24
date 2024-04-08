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
namespace OCA\Certificate24\Controller;

use OCA\Certificate24\AppInfo\Application;
use OCA\Certificate24\Client;
use OCA\Certificate24\Config;
use OCA\Certificate24\Requests;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\Image;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

if (!function_exists(__NAMESPACE__ . '\str_to_stream')) {
	function str_to_stream(string $string) {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $string);
		rewind($stream);
		return $stream;
	}
}

class DownloadController extends Controller {
	public const MAX_IMAGE_SIZE = 1024 * 1024;

	protected IUserSession $userSession;
	protected IURLGenerator $urlGenerator;
	protected Client $client;
	protected Config $config;
	protected Requests $requests;

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
	 * @BruteForceProtection(action=certificate24_request)
	 */
	public function downloadOriginal(string $id): Response {
		$req = $this->requests->getRequestById($id);
		if (!$req) {
			$response = new DataResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle();
			return $response;
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} elseif ($account['id'] !== $req['c24_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$user = $this->userSession->getUser();
		if (!$this->requests->mayAccess($user, $req)) {
			$redirectUrl = $this->urlGenerator->linkToRoute(Application::APP_ID . '.Download.downloadOriginal', ['id' => $id]);
			$response = new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', [
				'redirect_url' => $redirectUrl,
			]));
			$response->throttle();
			return $response;
		}

		$url = $this->client->getOriginalUrl($req['c24_file_id'], $account, $req['c24_server']);
		$url .= (strpos($url, '?') === false) ? '?' : '&';
		$url .= 'download=1';
		return new RedirectResponse($url);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @BruteForceProtection(action=certificate24_request)
	 */
	public function downloadSource(string $id): Response {
		$req = $this->requests->getRequestById($id);
		if (!$req) {
			$response = new DataResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle();
			return $response;
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} elseif ($account['id'] !== $req['c24_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$user = $this->userSession->getUser();
		if (!$this->requests->mayAccess($user, $req)) {
			$redirectUrl = $this->urlGenerator->linkToRoute(Application::APP_ID . '.Download.downloadSource', ['id' => $id]);
			$response = new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', [
				'redirect_url' => $redirectUrl,
			]));
			$response->throttle();
			return $response;
		}

		$url = $this->client->getSourceUrl($req['c24_file_id'], $account, $req['c24_server']);
		$url .= (strpos($url, '?') === false) ? '?' : '&';
		$url .= 'download=1';
		return new RedirectResponse($url);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @BruteForceProtection(action=certificate24_request)
	 */
	public function downloadSigned(string $id): Response {
		$req = $this->requests->getRequestById($id);
		if (!$req) {
			$response = new DataResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle();
			return $response;
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} elseif ($account['id'] !== $req['c24_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$user = $this->userSession->getUser();
		if (!$this->requests->mayAccess($user, $req)) {
			$redirectUrl = $this->urlGenerator->linkToRoute(Application::APP_ID . '.Download.downloadSigned', ['id' => $id]);
			$response = new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', [
				'redirect_url' => $redirectUrl,
			]));
			$response->throttle();
			return $response;
		}

		$allSigned = true;
		foreach ($req['recipients'] as $recipient) {
			if (!$recipient['signed']) {
				$allSigned = false;
				break;
			}
		}

		if (!$allSigned) {
			// Document must be signed by all recipients to allow downloads.
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$url = $this->client->getSignedUrl($req['c24_file_id'], $account, $req['c24_server']);
		$url .= (strpos($url, '?') === false) ? '?' : '&';
		$url .= 'download=1';
		return new RedirectResponse($url);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function downloadSignatureImage() {
		$user = $this->userSession->getUser();
		$file = $this->config->getSignatureImage($user);
		if (!$file) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$content = $file->getContent();
		$mime = $file->getMimetype();
		if (!$mime || $mime === 'application/octet-stream') {
			$mime = mime_content_type(str_to_stream($content));
			if (!$mime) {
				$mime = 'application/octet-stream';
			}
		}

		$response = new DataDisplayResponse($content, HTTP::STATUS_OK, [
			'Content-Type' => $mime,
		]);
		$response->addHeader('Content-Disposition', null);
		$response->setETag($file->getEtag());
		$lastModified = new \DateTime();
		$lastModified->setTimestamp($file->getMTime());
		$response->setLastModified($lastModified);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteSignatureImage() {
		$user = $this->userSession->getUser();
		$this->config->deleteSignatureImage($user);
		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}

	/**
	 * @NoAdminRequired
	 */
	public function uploadSignatureImage() {
		$user = $this->userSession->getUser();

		$image = $this->request->getUploadedFile('image');
		if (!$image || !isset($image['error']) || is_array($image['error'])) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		if ($image['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($image['tmp_name'])) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		if ($image['size'] > self::MAX_IMAGE_SIZE) {
			return new DataResponse([], Http::STATUS_REQUEST_ENTITY_TOO_LARGE);
		}

		$data = file_get_contents($image['tmp_name']);
		$img = new Image();
		if (!$img->loadFromData($data) || !$img->valid()) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$this->config->storeSignatureImage($user, $data);
		return new DataResponse([], Http::STATUS_CREATED);
	}
}
