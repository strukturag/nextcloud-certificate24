<?php

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
namespace OCA\Certificate24\Tests\php;

use OCA\Certificate24\Config;
use OCA\Certificate24\Tokens;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
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
		$logger = $this->createMock(LoggerInterface::class);
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
		$token = $this->tokens->getToken($this->account, 'subject', 'action');
		$this->assertNotNull($token);

		$this->assertTrue($this->tokens->validateToken($token, $this->account, 'subject', 'action'));
		$this->assertFalse($this->tokens->validateToken($token, $this->account, 'another-subject', 'action'));
		$this->assertFalse($this->tokens->validateToken($token, $this->account, 'subject', 'another-action'));
	}

	public function testTokenInvalidAccount() {
		$this->timeFactory
			->method('getTime')
			->willReturn(time());
		$token = $this->tokens->getToken($this->account, 'subject', 'action');
		$this->assertNotNull($token);

		$invalidAccount = [
			'secret' => base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())),
		];
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, 'subject', 'action'));
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, 'another-subject', 'action'));
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, 'subject', 'another-action'));
	}

	public function testTokenExpired() {
		$this->timeFactory
			->method('getTime')
			->willReturn(time() - 10 * 60);
		$token = $this->tokens->getToken($this->account, 'subject', 'action');
		$this->assertNotNull($token);

		$invalidAccount = [
			'secret' => base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())),
		];
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, 'subject', 'action'));
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, 'another-subject', 'action'));
		$this->assertFalse($this->tokens->validateToken($token, $invalidAccount, 'subject', 'another-action'));
	}
}
