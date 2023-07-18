<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, struktur AG.
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

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use OCA\Esig\Client;
use OCA\Esig\Config;
use OCA\Esig\Verify;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class VerifyController extends OCSController {

	private LoggerInterface $logger;
	private IUserSession $userSession;
	private IRootFolder $root;
	private Config $config;
	private Verify $verify;
	private Client $client;

	public function __construct(string $appName,
		IRequest $request,
		LoggerInterface $logger,
		IUserSession $userSession,
		IRootFolder $root,
		Config $config,
		Verify $verify,
		Client $client) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->root = $root;
		$this->config = $config;
		$this->verify = $verify;
		$this->client = $client;
	}

	/**
	 * @NoAdminRequired
	 * @UserRateThrottle(limit=10, period=30)
	 *
	 * @param int $id
	 * @param bool $reverify
	 * @return DataResponse
	 */
	public function getFileSignatures(int $id, bool $reverify = false): DataResponse {
		$user = $this->userSession->getUser();
		$files = $this->root->getUserFolder($user->getUID())->getById($id);

		if (empty($files)) {
			return new DataResponse([
				'error' => 'unknown_file',
			], Http::STATUS_NOT_FOUND);
		}

		$file = $files[0];
		if (!$file->isReadable()) {
			return new DataResponse([
				'error' => 'error_accessing_file',
			], Http::STATUS_FORBIDDEN);
		}

		$signatures = null;
		if ($reverify) {
			$account = $this->config->getAccount();
			if (!$account['id'] || !$account['secret']) {
				return new DataResponse([
					'error' => 'unconfigured',
				], Http::STATUS_PRECONDITION_FAILED);
			}

			$server = $this->config->getApiServer();
			try {
				$signatures = $this->client->verifySignatures($file, $account, $server);
			} catch (ConnectException $e) {
				$this->logger->error('Error connecting to ' . $server, [
					'exception' => $e,
				]);
				return new DataResponse(['error' => 'error_connecting'], Http::STATUS_BAD_GATEWAY);
			} catch (\Exception $e) {
				switch ($e->getCode()) {
					case Http::STATUS_NOT_FOUND:
						/** @var BadResponseException $e */
						$response = $e->getResponse();
						$body = (string) $response->getBody();
						$signatures = json_decode($body, true);
						if ($signatures) {
							$this->verify->storeFileSignatures($file, $signatures);
						}
						return new DataResponse($signatures, Http::STATUS_NOT_FOUND);
				}

				$this->logger->error('Error sending request to ' . $server, [
					'exception' => $e,
				]);
				return new DataResponse(['error' => $e->getCode()], Http::STATUS_BAD_GATEWAY);
			}
			$this->verify->storeFileSignatures($file, $signatures);
		}

		if (!$signatures) {
			$signatures = $this->verify->getFileSignatures($file);
			if (!$signatures) {
				return new DataResponse([
					'error' => 'verify_pending',
				], Http::STATUS_PRECONDITION_FAILED);
			} elseif ($signatures['status'] === 'not_signed') {
				return new DataResponse($signatures, Http::STATUS_NOT_FOUND);
			}
		}

		return new DataResponse($signatures);
	}

	/**
	 * @return DataResponse
	 */
	public function clearCache(): DataResponse {
		$this->verify->deleteAllFileSignatures();
		return new DataResponse([
			'unverified_count' => $this->verify->getUnverifiedCount(),
		]);
	}

}
