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
namespace OCA\Certificate24;

use GuzzleHttp\Exception\BadResponseException;
use OCA\Certificate24\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class Client {
	private LoggerInterface $logger;
	private IClientService $clientService;
	private Config $config;
	private Tokens $tokens;
	private string $nextcloudVersion;
	private string $appVersion;

	public function __construct(LoggerInterface $logger,
		IClientService $clientService,
		IAppManager $appManager,
		IConfig $systemConfig,
		Config $config,
		Tokens $tokens) {
		$this->logger = $logger;
		$this->clientService = $clientService;
		$this->config = $config;
		$this->tokens = $tokens;
		$this->appVersion = $appManager->getAppVersion(Application::APP_ID);
		$this->nextcloudVersion = $systemConfig->getSystemValueString('version', '0.0.0');
	}

	private function getHeaders(?array $headers = null): array {
		if (!$headers) {
			$headers = [];
		}
		if (!isset($headers['User-Agent'])) {
			$headers['User-Agent'] = Application::APP_ID . '/' . $this->appVersion . ' Nextcloud/' . $this->nextcloudVersion;
		}
		return $headers;
	}

	public function shareFile(File $file, array $recipients, ?array $metadata, array $account, string $server): array {
		$token = $this->tokens->getToken($account, $file->getName(), 'upload');

		$client = $this->clientService->newClient();
		$headers = $this->getHeaders([
			'X-Vinegar-Token' => $token,
			'X-Vinegar-API' => 'true',
		]);
		$multipart = [
			[
				'name' => 'file',
				'contents' => $file->getContent(),
				'filename' => $file->getName(),
				'headers' => [
					'Content-Type' => strtolower($file->getMimeType()),
				],
			],
			[
				'name' => 'recipients',
				'contents' => json_encode($recipients),
				'headers' => [
					'Content-Type' => 'application/json; charset=UTF-8',
				],
			]
		];
		if (!empty($metadata)) {
			$multipart[] = [
				'name' => 'metadata',
				'contents' => json_encode($metadata),
				'headers' => [
					'Content-Type' => 'application/json; charset=UTF-8',
				],
			];
		}
		$response = $client->post($server . 'api/v1/files/' . rawurlencode($account['id']), [
			'headers' => $headers,
			'multipart' => $multipart,
			'verify' => !$this->config->insecureSkipVerify(),
			'timeout' => $this->config->getRequestTimeout(),
		]);
		$body = $response->getBody();
		return json_decode($body, true);
	}

	public function signFile(string $id, array $multipart, array $account, string $server): array {
		$token = $this->tokens->getToken($account, $id, 'sign');

		$client = $this->clientService->newClient();
		$headers = $headers = $this->getHeaders([
			'X-Vinegar-Token' => $token,
			'X-Vinegar-API' => 'true',
		]);
		$response = $client->post($server . 'api/v1/files/' . rawurlencode($account['id']) . '/sign/' . rawurlencode($id), [
			'headers' => $headers,
			'multipart' => $multipart,
			'verify' => !$this->config->insecureSkipVerify(),
			'timeout' => $this->config->getRequestTimeout(),
		]);
		$body = $response->getBody();
		return json_decode($body, true);
	}

	public function deleteFile(string $id, array $account, string $server): array {
		$token = $this->tokens->getToken($account, $id, 'delete');

		$client = $this->clientService->newClient();
		$headers = $headers = $this->getHeaders([
			'X-Vinegar-Token' => $token,
			'X-Vinegar-API' => 'true',
		]);
		$response = $client->delete($server . 'api/v1/files/' . rawurlencode($account['id']) . '/' . rawurlencode($id), [
			'headers' => $headers,
			'verify' => !$this->config->insecureSkipVerify(),
			'timeout' => $this->config->getRequestTimeout(),
		]);
		$body = $response->getBody();
		return json_decode($body, true);
	}

	public function getOriginalUrl(string $id, array $account, string $server): string {
		$url = $server . 'api/v1/files/' . rawurlencode($account['id']) . '/' . rawurlencode($id);
		$token = $this->tokens->getToken($account, $id, 'download-original');
		$url .= '?token=' . urlencode($token);
		return $url;
	}

	public function getSourceUrl(string $id, array $account, string $server): string {
		$url = $server . 'api/v1/files/' . rawurlencode($account['id']) . '/source/' . rawurlencode($id);
		$token = $this->tokens->getToken($account, $id, 'download-source');
		$url .= '?token=' . urlencode($token);
		return $url;
	}

	public function getSignedUrl(string $id, array $account, string $server): string {
		$url = $server . 'api/v1/files/' . rawurlencode($account['id']) . '/sign/' . rawurlencode($id);
		$token = $this->tokens->getToken($account, $id, 'download-signed');
		$url .= '?token=' . urlencode($token);
		return $url;
	}

	/**
	 * @return string|resource
	 */
	public function downloadSignedFile(string $id, array $account, string $server) {
		$url = $this->getSignedUrl($id, $account, $server);
		$headers = $this->getHeaders();
		$client = $this->clientService->newClient();
		$response = $client->get($url, [
			'headers' => $headers,
			'verify' => !$this->config->insecureSkipVerify(),
			'timeout' => $this->config->getRequestTimeout(),
		]);
		$body = $response->getBody();
		return $body;
	}

	/**
	 * @return string|resource
	 */
	public function getSignatureDetails(string $id, array $account, string $server, string $signature_id) {
		$url = $server . 'api/v1/files/' . rawurlencode($account['id']) . '/' . rawurlencode($id) . '/' . rawurlencode($signature_id) . '/details';
		$token = $this->tokens->getToken($account, $signature_id, 'signature-details');
		$url .= '?token=' . urlencode($token);

		$headers = $this->getHeaders();
		$client = $this->clientService->newClient();
		$response = $client->get($url, [
			'headers' => $headers,
			'verify' => !$this->config->insecureSkipVerify(),
			'timeout' => $this->config->getRequestTimeout(),
		]);
		$body = $response->getBody();
		return $body;
	}

	public function getDetailsUrl(string $id, string $server): string {
		if ($server === $this->config->getApiServer()) {
			$server = $this->config->getWebServer();
		}
		$url = $server . 'details/' . rawurlencode($id);
		return $url;
	}

	/**
	 * @return array
	 */
	public function verifySignatures(File $file, array $account, string $server) {
		$token = $this->tokens->getToken($account, $file->getName(), 'verify');

		$client = $this->clientService->newClient();
		$headers = $this->getHeaders([
			'X-Vinegar-Token' => $token,
			'X-Vinegar-API' => 'true',
		]);
		$multipart = [
			[
				'name' => 'file',
				'contents' => $file->getContent(),
				'filename' => $file->getName(),
				'headers' => [
					'Content-Type' => strtolower($file->getMimeType()),
				],
			],
		];

		try {
			$response = $client->post($server . 'api/v1/files/' . rawurlencode($account['id']) . '/verify', [
				'headers' => $headers,
				'multipart' => $multipart,
				'verify' => !$this->config->insecureSkipVerify(),
				'timeout' => $this->config->getRequestTimeout(),
			]);
			$body = $response->getBody();
			return json_decode($body, true);
		} catch (\Exception $e) {
			switch ($e->getCode()) {
				case Http::STATUS_BAD_REQUEST:
					// Fallthrough
				case Http::STATUS_NOT_FOUND:
					/** @var BadResponseException $e */
					$response = $e->getResponse();
					$body = (string) $response->getBody();
					$signatures = json_decode($body, true);
					if ($signatures) {
						return $signatures;
					}
					break;
			}

			/** @var \Exception $e */
			throw $e;
		}
	}

}
