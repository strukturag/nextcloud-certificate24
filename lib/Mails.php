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
namespace OCA\Esig;

use OC\Mail\Message;
use OCA\Esig\AppInfo\Application;
use OCP\Defaults;
use OCP\Files\File;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Util;
use Psr\Log\LoggerInterface;

class Mails {
	private IMailer $mailer;
	private Defaults $defaults;
	private IL10N $l10n;
	private IFactory $l10nFactory;
	private LoggerInterface $logger;
	private IURLGenerator $urlGenerator;
	private Config $config;
	private Requests $requests;

	public function __construct(
		IMailer $mailer,
		Defaults $defaults,
		IL10N $l10n,
		IFactory $l10nFactory,
		LoggerInterface $logger,
		IURLGenerator $urlGenerator,
		Config $config,
		Requests $requests) {
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->l10n = $l10n;
		$this->l10nFactory = $l10nFactory;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->requests = $requests;
	}

	private function renderTemplate(string $templateId, array $options, string $lang): string {
		$l10n = $this->l10nFactory->get(Application::APP_ID, $lang);
		try {
			$template = new TranslatedTemplate(Application::APP_ID, $templateId . '_' . $lang, $l10n);
		} catch (\Exception $e) {
			// Fallback to default template
			$template = new TranslatedTemplate(Application::APP_ID, $templateId, $l10n);
		}
		foreach ($options as $key => $value) {
			$template->assign($key, $value);
		}
		$result = $template->fetchPage();
		return trim($result);
	}

	public function sendRequestMail(string $id, IUser $user, File $file, array $recipient, string $server) {
		$signature_id = $recipient['esig_signature_id'] ?? null;
		if (!$signature_id) {
			$this->logger->error('No signature id found for request ' . $id . ' to send mail to ' . $recipient['value'], [
				'app' => Application::APP_ID,
			]);
			return;
		}

		if ($server && $server[strlen($server) - 1] != '/') {
			$server = $server . '/';
		}

		$lang = $this->l10n->getLanguageCode();
		$dn = $recipient['display_name'] ?? null;
		$templateOptions = [
			'file' => $file,
			'user' => $user,
			'recipient' => $dn ? $dn : $recipient['value'],
			'request_id' => $id,
			'url' => $server . 's/' . urlencode($signature_id),
		];

		$body = $this->renderTemplate('email.share.body', $templateOptions, $lang);
		$subject = $this->renderTemplate('email.share.subject', $templateOptions, $lang);

		$from = Util::getDefaultEmailAddress('noreply');
		/** @var Message $message */
		$message = $this->mailer->createMessage();
		$message->setFrom([$from => $this->defaults->getName()]);
		$to = [];
		if ($dn) {
			$to[$recipient['value']] = $dn;
		} else {
			$to[] = $recipient['value'];
		}
		$message->setTo($to);
		$message->setSubject($subject);
		$message->setPlainBody($body);
		$failed_recipients = $this->mailer->send($message);
		if (!empty($failed_recipients)) {
			$this->logger->error('Could not send email for request ' . $id . ' to ' . $recipient['value'], [
				'app' => Application::APP_ID,
			]);
			return;
		}

		$this->requests->markEmailSent($id, $recipient['value']);
		$this->logger->info('Sent email for request ' . $id . ' to ' . $recipient['value'], [
			'app' => Application::APP_ID,
		]);
	}

	public function sendLastSignatureMail(string $id, array $request, IUser $user, File $file, array $recipient) {
		$signature_id = $request['esig_signature_result_id'] ?? null;
		if (!$signature_id) {
			$this->logger->error('No signature result id found for request ' . $id . ' to send mail to ' . $recipient['value'], [
				'app' => Application::APP_ID,
			]);
			return;
		}

		$server = $request['esig_server'];
		if ($server && $server[strlen($server) - 1] != '/') {
			$server = $server . '/';
		}

		$lang = $this->l10n->getLanguageCode();
		$dn = $recipient['display_name'] ?? null;
		$templateOptions = [
			'file' => $file,
			'user' => $user,
			'recipient' => $dn ? $dn : $recipient['value'],
			'request_id' => $id,
			'url' => $server . 'details/' . urlencode($signature_id),
		];
		$body = $this->renderTemplate('email.lastsignature.body', $templateOptions, $lang);
		$subject = $this->renderTemplate('email.lastsignature.subject', $templateOptions, $lang);

		$from = Util::getDefaultEmailAddress('noreply');
		/** @var Message $message */
		$message = $this->mailer->createMessage();
		$message->setFrom([$from => $this->defaults->getName()]);
		$to = [];
		if ($dn) {
			$to[$recipient['value']] = $dn;
		} else {
			$to[] = $recipient['value'];
		}
		$message->setTo($to);
		$message->setSubject($subject);
		$message->setPlainBody($body);
		$failed_recipients = $this->mailer->send($message);
		if (!empty($failed_recipients)) {
			$this->logger->error('Could not send last signature email for request ' . $id . ' to ' . $recipient['value'], [
				'app' => Application::APP_ID,
			]);
			// TODO: Retry sending email.
			return;
		}

		$this->logger->info('Sent last signature email for request ' . $id . ' to ' . $recipient['value'], [
			'app' => Application::APP_ID,
		]);
	}
}
