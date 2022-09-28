<?php

declare(strict_types=1);

namespace OCA\Esig\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use GuzzleHttp\Exception\ConnectException;
use OCA\Esig\Config;
use OCA\Esig\Tokens;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;

class ApiController extends OCSController {

	const PDF_MIME_TYPES = [
		'application/pdf',
	];

	private IL10N $l10n;
	private ILogger $logger;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private IRootFolder $root;
	private IClientService $clientService;
	private IURLGenerator $urlGenerator;
	private ISecureRandom $secureRandom;
	private IDBConnection $db;
	private Config $config;
	private Tokens $tokens;

	public function __construct(string $appName,
								IRequest $request,
								IL10N $l10n,
								ILogger $logger,
								IUserManager $userManager,
								IUserSession $userSession,
								IRootFolder $root,
								IClientService $clientService,
								IURLGenerator $urlGenerator,
								ISecureRandom $secureRandom,
								IDBConnection $db,
								Config $config,
								Tokens $tokens) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->root = $root;
		$this->clientService = $clientService;
		$this->urlGenerator = $urlGenerator;
		$this->secureRandom = $secureRandom;
		$this->db = $db;
		$this->config = $config;
		$this->tokens = $tokens;
	}

	private function newRandomId(int $length): string {
		$chars = str_replace(['l', '0', '1'], '', ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
		return $this->secureRandom->generate($length, $chars);
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

		$token = $this->tokens->getToken($account, $file->getName());

		$client = $this->clientService->newClient();
		try {
			$server = $this->config->getServer();
			$headers = [
				'X-Vinegar-Token' => $token,
				'X-Vinegar-API' => 'true',
			];
			$response = $client->post($server . 'api/v1/files/' . rawurlencode($account['id']), [
				'headers' => $headers,
				'multipart' => [[
					'name' => 'file',
					'contents' => $file->getContent(),
					'filename' => $file->getName(),
					'headers' => [
						'Content-Type' => $mime,
					],
				]],
				'verify' => false,
				'nextcloud' => [
					'allow_local_address' => true,
				],
			]);
			$body = $response->getBody();
			$data = json_decode($body, true);
		} catch (ConnectException $e) {
			return new DataResponse(['error' => 'CAN_NOT_CONNECT'], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getCode()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$esig_file_id = $data['file_id'] ?? '';
		if (empty($esig_file_id)) {
			return new DataResponse(['error' => 'INVALID_RESPONSE'], Http::STATUS_BAD_GATEWAY);
		}

		$query = $this->db->getQueryBuilder();
		$query->insert('esig_requests')
			->values(
				[
					'id' => $query->createParameter('id'),
					'file_id' => $query->createNamedParameter($file->getId(), IQueryBuilder::PARAM_INT),
					'created' => $query->createFunction('now()'),
					'user_id' => $query->createNamedParameter($user->getUID()),
					'recipient' => $query->createNamedParameter($recipient),
					'recipient_type' => $query->createNamedParameter($recipient_type),
					'esig_account_id' => $query->createNamedParameter($account['id']),
					'esig_server' => $query->createNamedParameter($server),
					'esig_file_id' => $query->createNamedParameter($esig_file_id),
				]
			);

		$id = $this->newRandomId(16);
		while (true) {
			$query->setParameter('id', $id);
			try {
				$query->executeStatement();
			} catch (UniqueConstraintViolationException $e) {
				// Duplicate id, generate new.
				$id = $this->newRandomId(16);
				continue;
			}

			break;
		}
		return new DataResponse(['request_id' => $id], Http::STATUS_CREATED);
	}

	private function getOwnRequestFromRow($row, bool $include_signed): array {
		$r = [
			'request_id' => $row['id'],
			'created' => $row['created'],
			'file_id' => $row['file_id'],
			'recipient' => $row['recipient'],
			'recipient_type' => $row['recipient_type'],
		];
		if ($include_signed) {
			$r['signed'] = $row['signed'];
		}
		return $r;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param bool $include_signed
	 * @return DataResponse
	 */
	public function getRequests(?bool $include_signed = false): DataResponse {
		$user = $this->userSession->getUser();
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('user_id', $query->createNamedParameter($user->getUID())))
			->orderBy('created');

		if (!$include_signed) {
			$query->andWhere($query->expr()->isNull('signed'));
		}
		$result = $query->executeQuery();

		$requests = [];
		while ($row = $result->fetch()) {
			$requests[] = $this->getOwnRequestFromRow($row, $include_signed);
		}
		$result->closeCursor();

		return new DataResponse($requests);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function getRequest(string $id): DataResponse {
		$user = $this->userSession->getUser();
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('esig_requests')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)))
			->andWhere($query->expr()->eq('user_id', $query->createNamedParameter($user->getUID())));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$r = $this->getOwnRequestFromRow($row, true);
		return new DataResponse($r);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function deleteRequest(string $id): DataResponse {
		$user = $this->userSession->getUser();
		$query = $this->db->getQueryBuilder();
		$query->select('user_id', 'esig_file_id', 'esig_account_id', 'esig_server')
			->from('esig_requests')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false || $row['user_id'] !== $user->getUID()) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		} else if ($account['id'] !== $row['esig_account_id']) {
			return new DataResponse([], Http::STATUS_PRECONDITION_FAILED);
		}

		$token = $this->tokens->getToken($account, $row['esig_file_id']);

		$client = $this->clientService->newClient();
		try {
			$headers = [
				'X-Vinegar-Token' => $token,
				'X-Vinegar-API' => 'true',
			];
			$response = $client->delete($row['esig_server'] . 'api/v1/files/' . rawurlencode($row['esig_account_id']) . '/' . rawurlencode($row['esig_file_id']), [
				'headers' => $headers,
				'verify' => false,
				'nextcloud' => [
					'allow_local_address' => true,
				],
			]);
			$body = $response->getBody();
			$data = json_decode($body, true);
		} catch (ConnectException $e) {
			return new DataResponse(['error' => 'CAN_NOT_CONNECT'], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getCode()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$status = $data['status'] ?? '';
		if ($status !== 'success') {
			return new DataResponse(['error' => 'INVALID_RESPONSE'], Http::STATUS_BAD_GATEWAY);
		}

		$query->delete('esig_requests')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$query->executeStatement();

		return new DataResponse([]);
	}


}
