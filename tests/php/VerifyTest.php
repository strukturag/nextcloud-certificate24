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

use OCA\Certificate24\Requests;
use OCA\Certificate24\Verify;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class VerifyTest extends TestCase {
	/** @var MockObject|ITimeFactory $timeFactory */
	protected ITimeFactory $timeFactory;
	protected Verify $verify;

	public function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$db = \OC::$server->query(IDBConnection::class);
		$mimeTypeLoader = \OC::$server->query(IMimeTypeLoader::class);
		$logger = $this->createMock(LoggerInterface::class);
		$requests = $this->createMock(Requests::class);

		$this->verify = new Verify($logger, $db, $mimeTypeLoader, $requests);
		$this->verify->deleteAllFileSignatures();
		$this->verify->deleteAllFailed();
	}

	public function testIncrementFailed() {
		/** @var MockObject|File $file */
		$file = $this->createMock(File::class);
		$file
			->method('getId')
			->willReturn(1);

		$this->assertEquals(0, $this->verify->getFailedCount($file));
		$this->verify->storeFailed($file);
		$this->assertEquals(1, $this->verify->getFailedCount($file));
		$this->verify->storeFailed($file);
		$this->assertEquals(2, $this->verify->getFailedCount($file));
		$this->verify->deleteFailed($file);
		$this->assertEquals(0, $this->verify->getFailedCount($file));
	}
}
