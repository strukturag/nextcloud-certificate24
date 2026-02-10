<?php

/**
 * @copyright Copyright (c) 2026, struktur AG.
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
namespace OCA\Certificate24\Tests\php\Controller;

use OCA\Certificate24\Client;
use OCA\Certificate24\Config;
use OCA\Certificate24\Controller\ApiController;
use OCA\Certificate24\Mails;
use OCA\Certificate24\Manager;
use OCA\Certificate24\Metadata;
use OCA\Certificate24\Requests;
use OCA\Certificate24\Tokens;
use OCA\Certificate24\Validator;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ApiControllerTest extends TestCase {

	private ApiController $controller;
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var ISearch|MockObject */
	private $search;

	public function setUp(): void {
		parent::setUp();

		$this->search = $this->createMock(ISearch::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->controller = new ApiController(
			'certificate24',
			$this->createMock(IRequest::class),
			$this->createMock(LoggerInterface::class),
			$this->createMock(IAppManager::class),
			$this->createMock(IUserManager::class),
			$this->userSession,
			$this->createMock(IRootFolder::class),
			$this->search,
			$this->createMock(IMailer::class),
			$this->createMock(IFactory::class),
			$this->createMock(IL10N::class),
			$this->createMock(IConfig::class),
			$this->createMock(IURLGenerator::class),
			$this->createMock(Client::class),
			$this->createMock(Config::class),
			$this->createMock(Requests::class),
			$this->createMock(Metadata::class),
			$this->createMock(Validator::class),
			$this->createMock(Mails::class),
			$this->createMock(Manager::class),
			$this->createMock(Tokens::class),
		);
	}

	public function testSearchInvalid() {
		$this->search->expects($this->never())
			->method('search');
		$result = $this->controller->search('admin');
		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertEmpty($result->getData());
	}

	public function testSearchShortUser() {
		$this->search->expects($this->never())
			->method('search');
		$result = $this->controller->search('a', 'user');
		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
		$this->assertEmpty($result->getData());
	}

	public function testSearchShortEmail() {
		$this->search->expects($this->never())
			->method('search');
		$result = $this->controller->search('a', 'email');
		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
		$this->assertEmpty($result->getData());
	}

	public function testSearchWithResult() {
		// Nextcloud before 33 returns the own user for exact matches.
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('username');
		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn('Jane Doe');
		$user->expects($this->any())
			->method('getSystemEMailAddress')
			->willReturn('user@domain.invalid');

		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$userEntry = [
			'icon' => 'icon-user',
			'label' => 'Jane Doe',
			'shareWithDisplayNameUnique' => 'user@domain.invalid',
			'value' => [
				'shareType' => IShare::TYPE_USER,
				'shareWith' => 'username',
			],
		];
		$this->search->expects($this->once())
			->method('search')
			->with(
				'username',
				[IShare::TYPE_USER],
				false,
				10,
				0,
			)
			->willReturn([[
				'exact' => [
					'users' => [
						$userEntry,
					],
				]
			], false]);

		$result = $this->controller->search('username', 'user');
		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
		$this->assertEquals([
			'exact' => [
				'users' => [
					$userEntry,
				],
			],
		], $result->getData());
	}

	public function testSearchWithoutResult() {
		// Nextcloud 33+ doesn't return the own user in search results.
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('username');
		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn('Jane Doe');
		$user->expects($this->any())
			->method('getSystemEMailAddress')
			->willReturn('user@domain.invalid');

		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$userEntry = [
			'icon' => 'icon-user',
			'label' => 'Jane Doe',
			'shareWithDisplayNameUnique' => 'user@domain.invalid',
			'value' => [
				'shareType' => IShare::TYPE_USER,
				'shareWith' => 'username',
			],
		];
		$this->search->expects($this->once())
			->method('search')
			->with(
				'username',
				[IShare::TYPE_USER],
				false,
				10,
				0,
			)
			->willReturn([[], false]);

		$result = $this->controller->search('username', 'user');
		$this->assertEquals(Http::STATUS_OK, $result->getStatus());
		$this->assertEquals([
			'exact' => [
				'users' => [
					$userEntry,
				],
			],
		], $result->getData());
	}

}
