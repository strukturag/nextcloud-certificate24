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
namespace OCA\Esig\Tests\php;

use OCA\Esig\Config;
use OCA\Esig\Requests;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\IUser;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class RequestsTest extends TestCase {
	protected Requests $requests;
	protected array $requestIds;

	public function setUp(): void {
		parent::setUp();

		$logger = $this->createMock(LoggerInterface::class);
		$secureRandom = \OC::$server->query(ISecureRandom::class);
		$db = \OC::$server->getDatabaseConnection();
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$config = $this->createMock(Config::class);

		$this->requests = new Requests($logger, $secureRandom, $db, $dispatcher, $config);
		$this->requestIds = [];
	}

	public function tearDown(): void {
		foreach ($this->requestIds as $id) {
			$this->requests->deleteRequestById($id);
		}
		parent::tearDown();
	}

	private function checkRequest(string $id, array $recipients, array $metadata, string $esig_file_id, File $file, IUser $user, bool $saved = false): array {
		$request1 = $this->requests->getRequestById($id);
		$this->assertNotNull($request1);
		$this->assertEquals($recipients, $request1['recipients']);
		$this->assertEquals($metadata, $request1['metadata']);
		$this->assertEquals($saved, $request1['saved']);

		$this->assertTrue($this->requests->mayAccess($user, $request1));

		$request2 = $this->requests->getOwnRequestById($user, $id);
		$this->assertEquals($request1, $request2);

		$request2 = $this->requests->getRequestByEsigFileId($esig_file_id);
		$this->assertEquals($request1, $request2);

		foreach ($recipients as $r) {
			$request2 = $this->requests->getRequestByEsigSignatureId($r['esig_signature_id']);
			$this->assertEquals($request1, $request2);
		}

		$requests = $this->requests->getOwnRequests($user, true);
		$this->assertEquals(1, count($requests));
		$this->assertEquals($request1, $requests[0]);

		$requests = $this->requests->getRequestsForFile($file, true);
		$this->assertEquals(1, count($requests));
		$this->assertEquals($request1, $requests[0]);

		$allSigned = true;
		foreach ($recipients as $r) {
			if (!$r['signed']) {
				$allSigned = false;
				break;
			}
		}
		if ($allSigned) {
			$requests = $this->requests->getOwnRequests($user, false);
			$this->assertEquals(0, count($requests));

			$requests = $this->requests->getRequestsForFile($file, false);
			$this->assertEquals(0, count($requests));
		} else {
			$requests = $this->requests->getOwnRequests($user, false);
			$this->assertEquals(1, count($requests));
			$this->assertEquals($request1, $requests[0]);

			$requests = $this->requests->getRequestsForFile($file, false);
			$this->assertEquals(1, count($requests));
			$this->assertEquals($request1, $requests[0]);
		}

		foreach ($recipients as $r) {
			if ($r['type'] !== 'user') {
				continue;
			}

			/** @var MockObject|IUser $u */
			$u = $this->createMock(IUser::class);
			$u
				->method('getUID')
				->willReturn($r['value']);

			$this->assertTrue($this->requests->mayAccess($u, $request1));

			$requests = $this->requests->getIncomingRequests($u, true);
			$this->assertEquals(1, count($requests));
			$this->assertEquals($request1, $requests[0]);

			if ($allSigned) {
				$requests = $this->requests->getIncomingRequests($u, false);
				$this->assertEquals(0, count($requests));
			} else {
				$requests = $this->requests->getIncomingRequests($u, false);
				$this->assertEquals(1, count($requests));
				$this->assertEquals($request1, $requests[0]);
			}
		}

		return $request1;
	}

	private function checkDeletedRequest(string $id, array $recipients, string $esig_file_id, File $file, IUser $user) {
		$request = $this->requests->getRequestById($id);
		$this->assertNull($request);

		$request = $this->requests->getOwnRequestById($user, $id);
		$this->assertNull($request);

		$request = $this->requests->getRequestByEsigFileId($esig_file_id);
		$this->assertNull($request);

		foreach ($recipients as $r) {
			$request = $this->requests->getRequestByEsigSignatureId($r['esig_signature_id']);
			$this->assertNull($request);
		}

		$requests = $this->requests->getOwnRequests($user, true);
		$this->assertEquals(0, count($requests));

		$requests = $this->requests->getOwnRequests($user, false);
		$this->assertEquals(0, count($requests));

		$requests = $this->requests->getRequestsForFile($file, true);
		$this->assertEquals(0, count($requests));

		$requests = $this->requests->getRequestsForFile($file, false);
		$this->assertEquals(0, count($requests));

		foreach ($recipients as $r) {
			if ($r['type'] !== 'user') {
				continue;
			}

			/** @var MockObject|IUser $u */
			$u = $this->createMock(IUser::class);
			$u
				->method('getUID')
				->willReturn($r['value']);

			$requests = $this->requests->getIncomingRequests($u, true);
			$this->assertEquals(0, count($requests));

			$requests = $this->requests->getIncomingRequests($u, false);
			$this->assertEquals(0, count($requests));
		}
	}

	public function testStoreRequestSingleUser() {
		/** @var MockObject|File $file */
		$file = $this->createMock(File::class);
		$file
			->method('getId')
			->willReturn(1234);
		/** @var MockObject|IUser $user */
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('admin');
		$recipients = [
			[
				'type' => 'user',
				'value' => 'johndoe',
				'public_id' => 'john-signature-id',
			],
		];
		$options = null;
		$metadata = [
			'foo' => 'bar',
			'baz' => 123,
		];
		$account = [
			'id' => 'the-account',
		];
		$server = 'https://domain.invalid';
		$esig_file_id = 'the-file';
		$esig_signature_result_id = 'the-signature-result';

		$id = $this->requests->storeRequest($file, $user, $recipients, $options, $metadata, $account, $server, $esig_file_id, $esig_signature_result_id);
		$this->assertNotNull($id);
		$this->requestIds[] = $id;

		$recipients2 = $recipients;
		$recipients2[0]['esig_signature_id'] = $recipients[0]['public_id'];
		unset($recipients2[0]['public_id']);
		$recipients2[0]['signed'] = null;

		$request = $this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user);

		$pending = $this->requests->getPendingSignatures();
		$this->assertEmpty($pending['single']);
		$this->assertEmpty($pending['multi']);

		$signed = new \DateTime();
		// Round to seconds, required as some databases don't store with sub-second precision.
		$signed->setTimestamp($signed->getTimestamp());
		$isLast = $this->requests->markRequestSignedById($id, $recipients[0]['type'], $recipients[0]['value'], $signed);
		$this->assertTrue($isLast);

		$recipients2[0]['signed'] = $signed;
		$request = $this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user);
		$request['last_signed'] = $signed;

		$pending = $this->requests->getPendingDownloads();
		$this->assertEquals(1, count($pending));
		$this->assertEquals($request, $pending[0]);

		$this->requests->markRequestSavedById($id);
		$pending = $this->requests->getPendingDownloads();
		$this->assertEquals(0, count($pending));
		$this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user, true);

		$this->requests->markRequestDeletedById($id);
		$this->checkDeletedRequest($id, $recipients2, $esig_file_id, $file, $user);
	}

	public function testStoreRequestSingleMail() {
		/** @var MockObject|File $file */
		$file = $this->createMock(File::class);
		$file
			->method('getId')
			->willReturn(1234);
		/** @var MockObject|IUser $user */
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('admin');
		$recipients = [
			[
				'type' => 'email',
				'value' => 'user@domain.invalid',
				'public_id' => 'user-signature-id',
			],
		];
		$options = null;
		$metadata = [
			'foo' => 'bar',
			'baz' => 123,
		];
		$account = [
			'id' => 'the-account',
		];
		$server = 'https://domain.invalid';
		$esig_file_id = 'the-file';
		$esig_signature_result_id = 'the-signature-result';

		$id = $this->requests->storeRequest($file, $user, $recipients, $options, $metadata, $account, $server, $esig_file_id, $esig_signature_result_id);
		$this->assertNotNull($id);
		$this->requestIds[] = $id;

		$recipients2 = $recipients;
		$recipients2[0]['esig_signature_id'] = $recipients[0]['public_id'];
		unset($recipients2[0]['public_id']);
		$recipients2[0]['signed'] = null;

		$request = $this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user);

		$pending = $this->requests->getPendingSignatures();
		$this->assertEquals(1, count($pending['single']));
		$this->assertEquals($request, $pending['single'][0]);
		$this->assertEmpty($pending['multi']);

		$signed = new \DateTime();
		// Round to seconds, required as some databases don't store with sub-second precision.
		$signed->setTimestamp($signed->getTimestamp());
		$isLast = $this->requests->markRequestSignedById($id, $recipients[0]['type'], $recipients[0]['value'], $signed);
		$this->assertTrue($isLast);

		$recipients2[0]['signed'] = $signed;
		$request = $this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user);

		$pending = $this->requests->getPendingSignatures();
		$this->assertEmpty($pending['single']);
		$this->assertEmpty($pending['multi']);

		$pending = $this->requests->getPendingEmails();
		$this->assertEquals(1, count($pending['single']));
		$this->assertEquals($request, $pending['single'][0]);
		$this->assertEquals(0, count($pending['multi']));

		$this->requests->markEmailSent($id, $recipients[0]['value']);
		$request = $this->requests->getRequestById($id);

		$pending = $this->requests->getPendingEmails();
		$this->assertEquals(0, count($pending['single']));
		$this->assertEquals(0, count($pending['multi']));

		$request['last_signed'] = $signed;
		$pending = $this->requests->getPendingDownloads();
		$this->assertEquals(1, count($pending));
		$this->assertEquals($request, $pending[0]);

		$this->requests->markRequestSavedById($id);
		$pending = $this->requests->getPendingDownloads();
		$this->assertEquals(0, count($pending));
		$this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user, true);

		$this->requests->markRequestDeletedById($id);
		$this->checkDeletedRequest($id, $recipients2, $esig_file_id, $file, $user);
	}

	public function testStoreRequestMultiple() {
		/** @var MockObject|File $file */
		$file = $this->createMock(File::class);
		$file
			->method('getId')
			->willReturn(1234);
		/** @var MockObject|IUser $user */
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('admin');
		$recipients = [
			[
				'type' => 'user',
				'value' => 'johndoe',
				'public_id' => 'john-signature-id',
			],
			[
				'type' => 'email',
				'value' => 'user@domain.invalid',
				'public_id' => 'user-signature-id',
			],
		];
		$options = null;
		$metadata = [
			'foo' => 'bar',
			'baz' => 123,
		];
		$account = [
			'id' => 'the-account',
		];
		$server = 'https://domain.invalid';
		$esig_file_id = 'the-file';
		$esig_signature_result_id = 'the-signature-result';

		$id = $this->requests->storeRequest($file, $user, $recipients, $options, $metadata, $account, $server, $esig_file_id, $esig_signature_result_id);
		$this->assertNotNull($id);
		$this->requestIds[] = $id;

		$recipients2 = $recipients;
		foreach ($recipients2 as &$r) {
			$r['esig_signature_id'] = $r['public_id'];
			unset($r['public_id']);
			$r['signed'] = null;
		}

		$request = $this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user);

		$pending = $this->requests->getPendingSignatures();
		$this->assertEmpty($pending['single']);
		$this->assertEquals(1, count($pending['multi']));
		unset($pending['multi'][0]['id']);
		unset($pending['multi'][0]['created']);
		$this->assertEquals([
			'request_id' => $id,
			'type' => $recipients[1]['type'],
			'value' => $recipients[1]['value'],
			'signed' => null,
			'saved' => null,
			'esig_signature_id' => $recipients[1]['public_id'],
			'email_sent' => null,
			'request' => $request,
		], $pending['multi'][0]);

		$signed1 = new \DateTime();
		// Round to seconds, required as some databases don't store with sub-second precision.
		$signed1->setTimestamp($signed1->getTimestamp());
		$isLast = $this->requests->markRequestSignedById($id, $recipients[0]['type'], $recipients[0]['value'], $signed1);
		$this->assertFalse($isLast);

		$recipients2[0]['signed'] = $signed1;
		$request = $this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user);

		$pending = $this->requests->getPendingSignatures();
		$this->assertEmpty($pending['single']);
		$this->assertEquals(1, count($pending['multi']));
		unset($pending['multi'][0]['id']);
		unset($pending['multi'][0]['created']);
		$this->assertEquals([
			'request_id' => $id,
			'type' => $recipients[1]['type'],
			'value' => $recipients[1]['value'],
			'signed' => null,
			'saved' => null,
			'esig_signature_id' => $recipients[1]['public_id'],
			'email_sent' => null,
			'request' => $request,
		], $pending['multi'][0]);

		$signed2 = new \DateTime();
		$signed2 = $signed2->add(new \DateInterval('PT1H'));
		// Round to seconds, required as some databases don't store with sub-second precision.
		$signed2->setTimestamp($signed2->getTimestamp());
		$isLast = $this->requests->markRequestSignedById($id, $recipients[1]['type'], $recipients[1]['value'], $signed2);
		$this->assertTrue($isLast);

		$recipients2[1]['signed'] = $signed2;
		$request = $this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user);

		$pending = $this->requests->getPendingSignatures();
		$this->assertEmpty($pending['single']);
		$this->assertEmpty($pending['multi']);

		$pending = $this->requests->getPendingEmails();
		$this->assertEquals(0, count($pending['single']));
		$this->assertEquals(1, count($pending['multi']));
		unset($pending['multi'][0]['id']);
		unset($pending['multi'][0]['created']);
		$this->assertEquals([
			'request_id' => $id,
			'type' => $recipients[1]['type'],
			'value' => $recipients[1]['value'],
			'signed' => $signed2,
			'saved' => null,
			'esig_signature_id' => $recipients[1]['public_id'],
			'email_sent' => null,
			'request' => $request,
		], $pending['multi'][0]);

		$this->requests->markEmailSent($id, $recipients[1]['value']);

		$pending = $this->requests->getPendingEmails();
		$this->assertEquals(0, count($pending['single']));
		$this->assertEquals(0, count($pending['multi']));

		$pending = $this->requests->getPendingDownloads();
		$this->assertEquals(1, count($pending));
		$request['last_signed'] = $signed2;
		$this->assertEquals($request, $pending[0]);

		$this->requests->markRequestSavedById($id);
		$pending = $this->requests->getPendingDownloads();
		$this->assertEquals(0, count($pending));
		$this->checkRequest($id, $recipients2, $metadata, $esig_file_id, $file, $user, true);

		$this->requests->markRequestDeletedById($id);
		$this->checkDeletedRequest($id, $recipients2, $esig_file_id, $file, $user);
	}
}
