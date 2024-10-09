<?php

/**
 * @copyright Copyright (c) 2024, struktur AG.
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

use OC\Mail\Message;
use OCA\Certificate24\AppInfo\Application;
use OCA\Certificate24\Config;
use OCA\Certificate24\Mails;
use OCA\Certificate24\Requests;
use OCP\Defaults;
use OCP\Files\File;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class MailsTest extends TestCase {
	/** @var MockObject|IMailer $mailer */
	protected IMailer $mailer;
	/** @var MockObject|Requests $requests */
	protected Requests $requests;
	protected Mails $mails;

	public function setUp(): void {
		parent::setUp();

		$mailer = \OC::$server->get(IMailer::class);
		/** @var MockObject|IMailer */
		$this->mailer = $this->createMock(IMailer::class);
		$this->mailer
			->method('createMessage')
			->will($this->returnCallback(function () use ($mailer) {
				return $mailer->createMessage();
			}));
		$this->mailer
			->method('createEMailTemplate')
			->will($this->returnCallback(function ($id, $options) use ($mailer) {
				return $mailer->createEMailTemplate($id, $options);
			}));
		$defaults = \OC::$server->get(Defaults::class);
		$l10nFactory = \OC::$server->get(IFactory::class);
		$l10n = $l10nFactory->get(Application::APP_ID);
		$logger = \OC::$server->get(LoggerInterface::class);
		$urlGenerator = \OC::$server->get(IURLGenerator::class);
		$config = \OC::$server->get(Config::class);
		/** @var Requests */
		$this->requests = $this->createMock(Requests::class);

		$this->mails = new Mails(
			$this->mailer,
			$defaults,
			$l10n,
			$l10nFactory,
			$logger,
			$urlGenerator,
			$config,
			$this->requests,
		);
	}

	public function testRequestMail() {
		$id = 'request-id';
		/** @var MockObject|IUser $user */
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('admin');

		/** @var MockObject|File $file */
		$file = $this->createMock(File::class);
		$file
			->method('getId')
			->willReturn(1234);

		$recipient = [
			'type' => 'email',
			'value' => 'user@domain.invalid',
			'display_name' => 'Email User',
			'public_id' => 'john-signature-id',
			'c24_signature_id' => 'signature-id',
		];

		$server = 'https://domain.invalid';

		$this->mailer->expects($this->once())
			->method('send')
			->with($this->callback(function ($message) use ($recipient): bool {
				/** @var Message $message */
				$this->assertEquals(1, count($message->getTo()));
				$this->assertArrayHasKey($recipient['value'], $message->getTo());
				$this->assertEquals('Email User', $message->getTo()[$recipient['value']]);
				$this->assertEquals('Signing request on Nextcloud', $message->getSubject());
				return true;
			}));

		$this->requests->expects($this->once())
			->method('markEmailSent')
			->with($id, 'user@domain.invalid');

		$this->mails->sendRequestMail($id, $user, $file, $recipient, $server);
	}

	public function testLastSignatureMail() {
		$id = 'request-id';
		$request = [
			'c24_signature_result_id' => 'signature-result-id',
			'c24_server' => 'https://domain.invalid',
		];

		/** @var MockObject|IUser $user */
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('admin');

		/** @var MockObject|File $file */
		$file = $this->createMock(File::class);
		$file
			->method('getId')
			->willReturn(1234);
		$file
			->method('getName')
			->willReturn('testfile.pdf');

		$recipient = [
			'type' => 'email',
			'value' => 'user@domain.invalid',
			'display_name' => 'Email User',
			'public_id' => 'john-signature-id',
			'c24_signature_id' => 'signature-id',
		];

		$this->mailer->expects($this->once())
			->method('send')
			->with($this->callback(function ($message) use ($recipient): bool {
				/** @var Message $message */
				$this->assertEquals(1, count($message->getTo()));
				$this->assertArrayHasKey($recipient['value'], $message->getTo());
				$this->assertEquals('Email User', $message->getTo()[$recipient['value']]);
				$this->assertEquals('Signatures finished for "testfile.pdf" on Nextcloud', $message->getSubject());
				return true;
			}));

		$this->mails->sendLastSignatureMail($id, $request, $user, $file, $recipient);
	}
}
