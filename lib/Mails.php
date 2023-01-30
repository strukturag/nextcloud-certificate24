<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Requests;
use OCA\Esig\TranslatedTemplate;
use OCP\Defaults;
use OCP\Files\File;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Util;

class Mails {

	private IMailer $mailer;
	private Defaults $defaults;
	private IL10N $l10n;
	private IFactory $l10nFactory;
	private ILogger $logger;
	private IURLGenerator $urlGenerator;
	private Requests $requests;

	public function __construct(
								IMailer $mailer,
								Defaults $defaults,
								IL10N $l10n,
								IFactory $l10nFactory,
								ILogger $logger,
								IURLGenerator $urlGenerator,
								Requests $requests) {
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->l10n = $l10n;
		$this->l10nFactory = $l10nFactory;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
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

	public function sendRequestMail(string $id, IUser $user, File $file, array $recipients, array $recipient) {
		$lang = $this->l10n->getLanguageCode();
		$templateOptions = [
			'file' => $file,
			'user' => $user,
			'recipient' => $recipient['value'],
			'request_id' => $id,
			'url' => $this->urlGenerator->linkToRouteAbsolute('esig.Page.sign', ['id' => $id]),
		];
		$body = $this->renderTemplate('email.share.body', $templateOptions, $lang);
		$subject = $this->renderTemplate('email.share.subject', $templateOptions, $lang);

		$from = Util::getDefaultEmailAddress('noreply');
		/** @var \OC\Mail\Message $message */
		$message = $this->mailer->createMessage();
		$message->setFrom([$from => $this->defaults->getName()]);
		$message->setTo([$recipient['value']]);
		$message->setSubject($subject);
		$message->setPlainBody($body);
		$failed_recipients = $this->mailer->send($message);
		if (!empty($failed_recipients)) {
			$this->logger->error('Could not send email to ' . $recipient['value'], [
				'app' => Application::APP_ID,
			]);
			return;
		}

		$this->requests->markEmailSent($id, $recipients, $recipient['value']);
		$this->logger->info('Sent email to ' . $recipient['value'], [
			'app' => Application::APP_ID,
		]);
	}

}
