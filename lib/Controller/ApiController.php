<?php

declare(strict_types=1);

namespace OCA\Esig\Controller;

use GuzzleHttp\Exception\ConnectException;
use OCA\Esig\AppInfo\Application;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Events\SignEvent;
use OCA\Esig\Requests;
use OCA\Esig\TranslatedTemplate;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Share\IShare;
use OCP\Util;

function str_to_stream(string $string) {
	$stream = fopen('php://memory','r+');
	fwrite($stream, $string);
	rewind($stream);
	return $stream;
}

class ApiController extends OCSController {

	const ISO8601_EXTENDED = "Y-m-d\TH:i:s.uP";

	const PDF_MIME_TYPES = [
		'application/pdf',
	];

	const MAX_SIGN_OPTIONS_SIZE = 8 * 1024;

	private IL10N $l10n;
	private IFactory $l10nFactory;
	private ILogger $logger;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private IRootFolder $root;
	private IURLGenerator $urlGenerator;
	private ISearch $search;
	private IMailer $mailer;
	private IEventDispatcher $dispatcher;
	private Client $client;
	private Config $config;
	private Requests $requests;

	public function __construct(string $appName,
								IRequest $request,
								IL10N $l10n,
								IFactory $l10nFactory,
								ILogger $logger,
								IUserManager $userManager,
								IUserSession $userSession,
								IRootFolder $root,
								IURLGenerator $urlGenerator,
								ISearch $search,
								IMailer $mailer,
								IEventDispatcher $dispatcher,
								Client $client,
								Config $config,
								Requests $requests) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->l10nFactory = $l10nFactory;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->root = $root;
		$this->urlGenerator = $urlGenerator;
		$this->search = $search;
		$this->mailer = $mailer;
		$this->dispatcher = $dispatcher;
		$this->client = $client;
		$this->config = $config;
		$this->requests = $requests;
	}

	private function parseDateTime($s) {
		if (!$s) {
			return null;
		}
		if ($s[strlen($s) - 1] === 'Z') {
			$s = substr($s, 0, strlen($s) - 1) . '+00:00';
		}
		if ($s[strlen($s) - 3] !== ':') {
			$s = $s . ':00';
		}
		if ($s[10] === ' ') {
			$s[10] = 'T';
		}
		if (strlen($s) === 19) {
			// SQLite backend stores without timezone, e.g. "2022-10-12 06:54:54".
			$s .= '+00:00';
		}
		$dt = \DateTime::createFromFormat(\DateTime::ISO8601, $s);
		if (!$dt) {
			$dt = \DateTime::createFromFormat(self::ISO8601_EXTENDED, $s);
		}
		if (!$dt) {
			$this->logger->error('Could not convert ' . $s . ' to datetime', [
				'app' => Application::APP_ID,
			]);
			$dt = null;
		}
		return $dt;
	}

	private function formatDateTime($dt) {
		if (!$dt) {
			return null;
		}
		if (is_string($dt)) {
			$dt = $this->parseDateTime($dt);
			if (!$dt) {
				return null;
			}
		}
		return $dt->format(\DateTime::RFC3339);
	}

	private function renderTemplate(string $templateId, array $options, string $lang): string {
		$l10n = $this->l10nFactory->get(Application::APP_ID, $lang);
		try {
			$template = new TranslatedTemplate(Application::APP_ID, $templateId . '_' . $lang, $l10n);
		} catch (\Exception $e) {
			// Fallback to default template
			$template = new TranslatedTemplate(Application::APP_ID, $templateId, $l10n);
		}
		foreach ($options as $key => $value) {
			$template->assign($key, $value);
		}
		$result = $template->fetchPage();
		return trim($result);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $file_id
	 * @param string $recipient
	 * @param string $recipient_type
	 * @param ?array $metadata
	 * @return DataResponse
	 */
	public function shareFile(int $file_id, string $recipient, string $recipient_type, ?array $metadata = null): DataResponse {
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([
				'error' => 'unconfigured',
			], Http::STATUS_PRECONDITION_FAILED);
		}

		switch ($recipient_type) {
			case 'email':
				if (!$this->mailer->validateMailAddress($recipient)) {
					return new DataResponse([
						'error' => 'invalid_email',
					], Http::STATUS_BAD_REQUEST);
				}
				$recipientUser = null;
				break;
			case 'user':
				$recipientUser = $this->userManager->get($recipient);
				if (!$recipientUser) {
					return new DataResponse([
						'error' => 'unknown_user',
					], Http::STATUS_NOT_FOUND);
				}
				break;
			default:
				return new DataResponse([
					'error' => 'invalid_recipient_type',
				], Http::STATUS_BAD_REQUEST);
		}

		// TODO: Validate metadata format.

		$user = $this->userSession->getUser();
		if ($user) {
			$files = $this->root->getUserFolder($user->getUID())->getById($file_id);
		} else {
			$files = $this->root->getById($file_id);
		}

		if (empty($files)) {
			return new DataResponse([
				'error' => 'unknown_file',
			], Http::STATUS_NOT_FOUND);
		}

		$file = $files[0];
		if (!$file->isReadable() || !$file->isUpdateable()) {
			return new DataResponse([
				'error' => 'error_accessing_file',
			], Http::STATUS_FORBIDDEN);
		}

		$mime = $file->getMimeType();
		if ($mime) {
			$mime = strtolower($mime);
		}
		if (!in_array($mime, self::PDF_MIME_TYPES)) {
			return new DataResponse([
				'error' => 'invalid_filetype',
			], Http::STATUS_BAD_REQUEST);
		}

		$server = $this->config->getServer();
		try {
			$data = $this->client->shareFile($file, $metadata, $account, $server);
		} catch (ConnectException $e) {
			return new DataResponse(['error' => 'error_connecting'], Http::STATUS_BAD_GATEWAY);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getCode()], Http::STATUS_BAD_GATEWAY);
		}

		$esig_file_id = $data['file_id'] ?? '';
		if (empty($esig_file_id)) {
			return new DataResponse(['error' => 'invalid_response'], Http::STATUS_BAD_GATEWAY);
		}

		$id = $this->requests->storeRequest($file, $user, $recipient, $recipient_type, $metadata, $account, $server, $esig_file_id);
		if ($recipient_type === 'email') {
			$lang = $this->l10n->getLanguageCode();
			$templateOptions = [
				'file' => $file,
				'user' => $user,
				'recipient' => $recipient,
				'request_id' => $id,
				'url' => $this->urlGenerator->linkToRouteAbsolute('esig.Page.sign', ['id' => $id]),
			];
			$body = $this->renderTemplate('email.share.body', $templateOptions, $lang);
			$subject = $this->renderTemplate('email.share.subject', $templateOptions, $lang);

			$from = Util::getDefaultEmailAddress('noreply');
			$defaults = \OC::$server->query(Defaults::class);
			$message = $this->mailer->createMessage();
			$message->setFrom([$from => $defaults->getName()]);
			$message->setTo([$recipient]);
			$message->setSubject($subject);
			$message->setPlainBody($body);
			$failed_recipients = $this->mailer->send($message);
			if (!empty($failed_recipients)) {
				// TODO: Should we delete the request?
				return new DataResponse(['error' => 'error_sending_email'], Http::STATUS_INTERNAL_ERROR);
			}
		}

		return new DataResponse(['request_id' => $id], Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param bool $include_signed
	 * @return DataResponse
	 */
	public function getRequests(?bool $include_signed = false): DataResponse {
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

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

			$r = [
				'request_id' => $request['id'],
				'created' => $this->formatDateTime($request['created']),
				'file_id' => $request['file_id'],
				'filename' => $file->getName(),
				'mimetype' => $mime,
				'download_url' => $this->client->getOriginalUrl($request['esig_file_id'], $account, $request['esig_server']),
				'recipient' => $request['recipient'],
				'recipient_type' => $request['recipient_type'],
				'metadata' => $request['metadata'],
			];
			if ($include_signed && $request['signed']) {
				$r['signed'] = $this->formatDateTime($request['signed']);
				$r['signed_url'] = $this->client->getSignedUrl($request['esig_file_id'], $account, $request['esig_server']);
			}
			$response[] = $r;
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
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

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
				'request_id' => $request['id'],
				'created' => $this->formatDateTime($request['created']),
				'user_id' => $request['user_id'],
				'display_name' => $owner ? $owner->getDisplayName() : null,
				'filename' => $file->getName(),
				'mimetype' => $mime,
				'download_url' => $this->client->getOriginalUrl($request['esig_file_id'], $account, $request['esig_server']),
				'metadata' => $request['metadata'],
			];
			if ($include_signed && $request['signed']) {
				$r['signed'] = $this->formatDateTime($request['signed']);
				$r['signed_url'] = $this->client->getSignedUrl($request['esig_file_id'], $account, $request['esig_server']);
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
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

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

		$response = [
			'request_id' => $request['id'],
			'created' => $this->formatDateTime($request['created']),
			'file_id' => $request['file_id'],
			'filename' => $file->getName(),
			'mimetype' => $mime,
			'download_url' => $this->client->getOriginalUrl($request['esig_file_id'], $account, $request['esig_server']),
			'recipient' => $request['recipient'],
			'recipient_type' => $request['recipient_type'],
			'metadata' => $request['metadata'],
		];
		if (isset($request['signed']) && $request['signed']) {
			$response['signed'] = $this->formatDateTime($request['signed']);
			$response['signed_url'] = $this->client->getSignedUrl($request['esig_file_id'], $account, $request['esig_server']);
		}
		return new DataResponse($response);
	}

	/**
	 * @PublicPage
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function getIncomingRequest(string $id): DataResponse {
		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

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
			'created' => $this->formatDateTime($request['created']),
			'user_id' => $request['user_id'],
			'display_name' => $owner ? $owner->getDisplayName() : null,
			'filename' => $file->getName(),
			'mimetype' => $mime,
			'download_url' => $this->client->getOriginalUrl($request['esig_file_id'], $account, $request['esig_server']),
			'metadata' => $request['metadata'],
		];
		if (isset($response['signed']) && $request['signed']) {
			$response['signed'] = $this->formatDateTime($request['signed']);
			$response['signed_url'] = $this->client->getSignedUrl($request['esig_file_id'], $account, $request['esig_server']);
		}
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
	 * @PublicPage
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function signRequest(string $id): DataResponse {
		$user = $this->userSession->getUser();
		$row = $this->requests->getRequestById($id);
		if (!$row) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} else if ($row['recipient_type'] === 'user' && (!$user || $row['recipient'] !== $user->getUID())) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} else if ($account['id'] !== $row['esig_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$options = [];
		$optionsData = $this->request->getParam('options');
		if ($optionsData) {
			$options = json_decode($optionsData, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
			}
		}

		$signatureImages = [];

		$metadata = $row['metadata'] ?? [];
		$fields = $metadata['signature_fields'] ?? [];
		$embed = $options['embed_user_signature'] ?? false;
		if ($user && !empty($fields) && $embed) {
			$imageFile = $this->config->getSignatureImage($user);
			if ($imageFile) {
				$content = $imageFile->getContent();
				$mime = $imageFile->getMimetype();
				if (!$mime || $mime === 'application/octet-stream') {
					$mime = mime_content_type(str_to_stream($content));
					if (!$mime) {
						$mime = 'application/octet-stream';
					}
				}

				foreach ($fields as $field) {
					$signatureImages[] = [
						'name' => $field['id'],
						'filename' => $field['id'],
						'contents' => $content,
						'headers' => [
							'Content-Type' => $mime,
						],
					];
				};
			}
		}

		try {
			$data = $this->client->signFile($row['esig_file_id'], $signatureImages, $account, $row['esig_server']);
		} catch (ConnectException $e) {
			return new DataResponse(['error' => 'CAN_NOT_CONNECT'], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getCode()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$status = $data['status'] ?? '';
		if ($status !== 'success') {
			return new DataResponse(['error' => 'INVALID_RESPONSE'], Http::STATUS_BAD_GATEWAY);
		}

		$signed = $data['signed'] ?? null;
		if (is_string($signed)) {
			$signed = $this->parseDateTime($signed);
		}
		if (!$signed) {
			$signed = new \DateTime();
		}

		$this->requests->markRequestSignedById($id, $signed);

		$event = new SignEvent($id, $row, $user);
		$this->dispatcher->dispatch(SignEvent::class, $event);

		return new DataResponse([
			'request_id' => $id,
			'signed' => $this->formatDateTime($signed),
			'signed_url' => $this->client->getSignedUrl($row['esig_file_id'], $account, $row['esig_server']),
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $search
	 * @return DataResponse
	 */
	public function search(string $search = '', string $type = ''): DataResponse {
		$shareTypes = [];
		switch ($type) {
			case 'user':
				$shareTypes[] = IShare::TYPE_USER;
				break;
			case 'email':
				$shareTypes[] = IShare::TYPE_EMAIL;
				break;
		}
		if (empty($shareTypes)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$minLength = 3;
		if (strlen($search) < $minLength) {
			return new DataResponse([]);
		}

		$limit = 10;
		$offset = 0;
		$lookup = false;  // Don't use lookup server.
		[$result, $hasMoreResults] = $this->search->search($search, $shareTypes, $lookup, $limit, $offset);
		$response = new DataResponse($result);
		return $response;
	}

}
