<?php

declare(strict_types=1);

namespace OCA\Esig\Controller;

use GuzzleHttp\Exception\ConnectException;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Requests;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;

class ApiController extends OCSController {

	const PDF_MIME_TYPES = [
		'application/pdf',
	];

	private IL10N $l10n;
	private ILogger $logger;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private IRootFolder $root;
	private IURLGenerator $urlGenerator;
	private Client $client;
	private Config $config;
	private Requests $requests;

	public function __construct(string $appName,
								IRequest $request,
								IL10N $l10n,
								ILogger $logger,
								IUserManager $userManager,
								IUserSession $userSession,
								IRootFolder $root,
								IURLGenerator $urlGenerator,
								Client $client,
								Config $config,
								Requests $requests) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->root = $root;
		$this->urlGenerator = $urlGenerator;
		$this->client = $client;
		$this->config = $config;
		$this->requests = $requests;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $file_id
	 * @param string $recipient
	 * @param string $recipient_type
	 * @return DataResponse
	 */
	public function shareFile(int $file_id, string $recipient, string $recipient_type): DataResponse {
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		switch ($recipient_type) {
			case 'email':
				$recipientUser = null;
				break;
			case 'user':
				$recipientUser = $this->userManager->get($recipient);
				if (!$recipientUser) {
					return new DataResponse([], Http::STATUS_NOT_FOUND);
				}
				break;
			default:
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$user = $this->userSession->getUser();
		if ($user) {
			$files = $this->root->getUserFolder($user->getUID())->getById($file_id);
		} else {
			$files = $this->root->getById($file_id);
		}

		if (empty($files)) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$file = $files[0];
		if (!$file->isReadable() || !$file->isUpdateable()) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$mime = $file->getMimeType();
		if ($mime) {
			$mime = strtolower($mime);
		}
		if (!in_array($mime, self::PDF_MIME_TYPES)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$server = $this->config->getServer();
		try {
			$data = $this->client->shareFile($file, $account, $server);
		} catch (ConnectException $e) {
			return new DataResponse(['error' => 'CAN_NOT_CONNECT'], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getCode()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$esig_file_id = $data['file_id'] ?? '';
		if (empty($esig_file_id)) {
			return new DataResponse(['error' => 'INVALID_RESPONSE'], Http::STATUS_BAD_GATEWAY);
		}

		$id = $this->requests->storeRequest($file, $user, $recipient, $recipient_type, $account, $server, $esig_file_id);
		return new DataResponse(['request_id' => $id], Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param bool $include_signed
	 * @return DataResponse
	 */
	public function getRequests(?bool $include_signed = false): DataResponse {
		$user = $this->userSession->getUser();
		$requests = $this->requests->getOwnRequests($user, $include_signed);

		$root = $this->root->getUserFolder($user->getUID());
		$response = [];
		foreach ($requests as $request) {
			$files = $root->getById($request['file_id']);
			if (empty($files)) {
				// TODO: Should not happen, requests are deleted when files are.
				continue;
			}

			$file = $files[0];
			$mime = $file->getMimeType();
			if ($mime) {
				$mime = strtolower($mime);
			}

			$request['filename'] = $file->getName();
			$request['mimetype'] = $mime;
			$response[] = $request;
		}
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param bool $include_signed
	 * @return DataResponse
	 */
	public function getIncomingRequests(?bool $include_signed = false): DataResponse {
		$user = $this->userSession->getUser();
		$requests = $this->requests->getIncomingRequests($user, $include_signed);

		$response = [];
		foreach ($requests as $request) {
			$owner = $this->userManager->get($request['user_id']);
			if (!$owner) {
				// TODO: Should not happen, owned requests are deleted when users are.
				continue;
			}

			$files = $this->root->getUserFolder($owner->getUID())->getById($request['file_id']);
			if (empty($files)) {
				// TODO: Should not happen, requests are deleted when files are.
				continue;
			}

			$file = $files[0];
			$mime = $file->getMimeType();
			if ($mime) {
				$mime = strtolower($mime);
			}
			$r = [
				'request_id' => $request['request_id'],
				'created' => $request['created'],
				'user_id' => $request['user_id'],
				'display_name' => $owner ? $owner->getDisplayName() : null,
				'filename' => $file->getName(),
				'mimetype' => $mime,
			];
			if ($include_signed) {
				$r['signed'] = $request['signed'];
			}
			$response[] = $r;
		}

		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function getRequest(string $id): DataResponse {
		$user = $this->userSession->getUser();
		$request = $this->requests->getOwnRequestById($user, $id);
		if (!$request) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$files = $this->root->getUserFolder($user->getUID())->getById($request['file_id']);
		if (empty($files)) {
			// TODO: Should not happen, requests are deleted when files are.
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$file = $files[0];
		$mime = $file->getMimeType();
		if ($mime) {
			$mime = strtolower($mime);
		}

		$request['filename'] = $file->getName();
		$request['mimetype'] = $mime;
		return new DataResponse($request);
	}

	/**
	 * @PublicPage
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function getIncomingRequest(string $id): DataResponse {
		$request = $this->requests->getRequestById($id);
		if (!$request) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$user = $this->userSession->getUser();
		if (!$this->requests->mayAccess($user, $request)) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		$owner = $this->userManager->get($request['user_id']);
		if (!$owner) {
			// TODO: Should not happen, owned requests are deleted when users are.
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$files = $this->root->getUserFolder($owner->getUID())->getById($request['file_id']);
		if (empty($files)) {
			// TODO: Should not happen, requests are deleted when files are.
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$file = $files[0];
		$mime = $file->getMimeType();
		if ($mime) {
			$mime = strtolower($mime);
		}
		$response = [
			'request_id' => $id,
			'created' => $request['created'],
			'user_id' => $request['user_id'],
			'display_name' => $owner ? $owner->getDisplayName() : null,
			'filename' => $file->getName(),
			'mimetype' => $mime,
			'signed' => $request['signed'],
		];
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function deleteRequest(string $id): DataResponse {
		$user = $this->userSession->getUser();
		$row = $this->requests->getRequestById($id);
		if (!$row || $row['user_id'] !== $user->getUID()) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} else if ($account['id'] !== $row['esig_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		try {
			$data = $this->client->deleteFile($row['esig_file_id'], $account, $row['esig_server']);
		} catch (ConnectException $e) {
			return new DataResponse(['error' => 'CAN_NOT_CONNECT'], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getCode()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$status = $data['status'] ?? '';
		if ($status !== 'success') {
			return new DataResponse(['error' => 'INVALID_RESPONSE'], Http::STATUS_BAD_GATEWAY);
		}

		$this->requests->deleteRequestById($id);
		return new DataResponse([]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function signRequest(string $id): DataResponse {
		$user = $this->userSession->getUser();
		$row = $this->requests->getRequestById($id);
		if (!$row || $row['user_id'] !== $user->getUID()) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} else if ($account['id'] !== $row['esig_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		try {
			$data = $this->client->signFile($row['esig_file_id'], $account, $row['esig_server']);
		} catch (ConnectException $e) {
			return new DataResponse(['error' => 'CAN_NOT_CONNECT'], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getCode()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$status = $data['status'] ?? '';
		if ($status !== 'success') {
			return new DataResponse(['error' => 'INVALID_RESPONSE'], Http::STATUS_BAD_GATEWAY);
		}

		$this->requests->markRequestSignedById($id);
		return new DataResponse([]);
	}

}
