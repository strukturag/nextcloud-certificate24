<?php

namespace OCA\Esig\Tests\php;

use OCA\Esig\Config;
use OCA\Esig\Tokens;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ILogger;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TokensTest extends TestCase {
	/** @var MockObject|ITimeFactory $timeFactory */
	protected ITimeFactory $timeFactory;
	protected Tokens $tokens;
	protected array $account;

	public function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$urlGenerator = \OC::$server->query(IURLGenerator::class);
		$logger = $this->createMock(ILogger::class);
		$config = $this->createMock(Config::class);

		$this->tokens = new Tokens($this->timeFactory, $urlGenerator, $logger, $config);
		$keypair = sodium_crypto_sign_keypair();
		$this->account = [
			'secret' => base64_encode(sodium_crypto_sign_secretkey($keypair)),
		];
	}

	public function testToken() {
		$this->timeFactory
			->method('getTime')
			->willReturn(time());
		$token = $this->tokens->getToken($this->account, "subject", "action");
		$this->assertNotNull($token);

		$this->assertTrue($this->tokens->validateToken($token, $this->account, "subject", "action"));
		$this->assertFalse($this->tokens->validateToken($token, $this->account, "another-subject", "action"));
		$this->assertFalse($this->tokens->validateToken($token, $this->account, "subject", "another-action"));
	}

	public function testTokenInvalidAccount() {
		$this->timeFactory
			->method('getTime')
			->willReturn(time());
		$token = $this->tokens->getToken($this->account, "subject", "action");
		$this->assertNotNull($token);

		$invalidAccount = [
			'secret' => base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())),
		];
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, "subject", "action"));
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, "another-subject", "action"));
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, "subject", "another-action"));
	}

	public function testTokenExpired() {
		$this->timeFactory
			->method('getTime')
			->willReturn(time() - 10 * 60);
		$token = $this->tokens->getToken($this->account, "subject", "action");
		$this->assertNotNull($token);

		$invalidAccount = [
			'secret' => base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())),
		];
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, "subject", "action"));
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, "another-subject", "action"));
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, "subject", "another-action"));
	}
}
