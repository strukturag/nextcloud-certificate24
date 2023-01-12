<?php

declare(strict_types=1);

namespace OCA\Esig\Controller;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Requests;
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
use OCP\Util;

function str_to_stream(string $string) {
	$stream = fopen('php://memory','r+');
	fwrite($stream, $string);
	rewind($stream);
	return $stream;
}

class DownloadController extends Controller {

	const MAX_IMAGE_SIZE = 1024 * 1024;

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
	 * @BruteForceProtection(action=esig_request)
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
	 * @BruteForceProtection(action=esig_request)
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
		} else if ($account['id'] !== $req['esig_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$user = $this->userSession->getUser();
		if (!$this->requests->mayAccess($user, $req)) {
			$redirectUrl = $this->urlGenerator->linkToRoute('esig.Download.downloadSource', ['id' => $id]);
			$response = new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', [
				'redirect_url' => $redirectUrl,
			]));
			$response->throttle();
			return $response;
		}

		$url = $this->client->getSourceUrl($req['esig_file_id'], $account, $req['esig_server']);
		$url .= (strpos($url, '?') === false) ? '?' : '&';
		$url .= 'download=1';
		return new RedirectResponse($url);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @BruteForceProtection(action=esig_request)
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
