<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Esig\Tokens;
use OCP\Files\File;
use OCP\Http\Client\IClientService;
use OCP\ILogger;

class Client {

	private ILogger $logger;
	private IClientService $clientService;
	private Tokens $tokens;

	public function __construct(ILogger $logger,
								IClientService $clientService,
								Tokens $tokens) {
		$this->logger = $logger;
		$this->clientService = $clientService;
		$this->tokens = $tokens;
	}

	public function shareFile(File $file, array $recipients, ?array $metadata, array $account, string $server): array {
		$token = $this->tokens->getToken($account, $file->getName());

		$client = $this->clientService->newClient();
		$headers = [
			'X-Vinegar-Token' => $token,
			'X-Vinegar-API' => 'true',
		];
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
			'verify' => false,
			'nextcloud' => [
				'allow_local_address' => true,
			],
		]);
		$body = $response->getBody();
		return json_decode($body, true);
	}

	public function signFile(string $id, array $multipart, array $account, string $server): array {
		$token = $this->tokens->getToken($account, $id);

		$client = $this->clientService->newClient();
		$headers = [
			'X-Vinegar-Token' => $token,
			'X-Vinegar-API' => 'true',
		];
		$response = $client->post($server . 'api/v1/files/' . rawurlencode($account['id']) . '/sign/' . rawurlencode($id), [
			'headers' => $headers,
			'multipart' => $multipart,
			'verify' => false,
			'nextcloud' => [
				'allow_local_address' => true,
			],
		]);
		$body = $response->getBody();
		return json_decode($body, true);
	}

	public function deleteFile(string $id, array $account, string $server): array {
		$token = $this->tokens->getToken($account, $id);

		$client = $this->clientService->newClient();
		$headers = [
			'X-Vinegar-Token' => $token,
			'X-Vinegar-API' => 'true',
		];
		$response = $client->delete($server . 'api/v1/files/' . rawurlencode($account['id']) . '/' . rawurlencode($id), [
			'headers' => $headers,
			'verify' => false,
			'nextcloud' => [
				'allow_local_address' => true,
			],
		]);
		$body = $response->getBody();
		return json_decode($body, true);
	}

	public function getOriginalUrl(string $id, array $account, string $server): string {
		$url = $server . 'api/v1/files/' . rawurlencode($account['id']) . '/' . rawurlencode($id);
		$token = $this->tokens->getToken($account, $id);
		$url .= '?token=' . urlencode($token);
		return $url;
	}

	public function getSignedUrl(string $id, array $account, string $server): string {
		$url = $server . 'api/v1/files/' . rawurlencode($account['id']) . '/sign/' . rawurlencode($id);
		$token = $this->tokens->getToken($account, $id);
		$url .= '?token=' . urlencode($token);
		return $url;
	}

	public function downloadSignedFile(string $id, array $account, string $server): string {
		$url = $this->getSignedUrl($id, $account, $server);
		$client = $this->clientService->newClient();
		$response = $client->get($url, [
			'verify' => false,
			'nextcloud' => [
				'allow_local_address' => true,
			],
		]);
		$body = $response->getBody();
		return $body;
	}

}
