<?php

declare(strict_types=1);

namespace OCA\Esig\BackgroundJob;

use OCA\Esig\Mails;
use OCA\Esig\Requests;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\BackgroundJob\IJob;
use OCP\Files\IRootFolder;
use OCP\IUserManager;

class ResendMails extends TimedJob {

	private IUserManager $userManager;
	private IRootFolder $root;
	private Requests $requests;
	private Mails $mails;

	public function __construct(ITimeFactory $timeFactory,
								IUserManager $userManager,
								IRootFolder $root,
								Requests $requests,
								Mails $mails) {
		parent::__construct($timeFactory);

		// Every 5 minutes
		$this->setInterval(60 * 5);
		$this->setTimeSensitivity(IJob::TIME_SENSITIVE);

		$this->userManager = $userManager;
		$this->root = $root;
		$this->requests = $requests;
		$this->mails = $mails;
	}

	protected function run($argument): void {
		$pending = $this->requests->getPendingEmails();
		foreach ($pending['single'] as $entry) {
			$user = $this->userManager->get($entry['user_id']);
			if (!$user) {
				// Should not happen, requests will get deleted if the owner is deleted.
				continue;
			}

			$files = $this->root->getUserFolder($user->getUID())->getById($entry['file_id']);
			if (empty($files)) {
				// Should not happen, requests will get deleted if the associated file is deleted.
				continue;
			}

			$file = $files[0];
			$recipient = [
				'type' => $entry['recipient_type'],
				'value' => $entry['recipient'],
			];
			$this->mails->sendRequestMail($entry['id'], $user, $file, $recipient);
		}

		foreach ($pending['multi'] as $entry) {
			$user = $this->userManager->get($entry['request']['user_id']);
			if (!$user) {
				// Should not happen, requests will get deleted if the owner is deleted.
				continue;
			}

			$files = $this->root->getUserFolder($user->getUID())->getById($entry['request']['file_id']);
			if (empty($files)) {
				// Should not happen, requests will get deleted if the associated file is deleted.
				continue;
			}

			$file = $files[0];
			$recipient = [
				'type' => $entry['type'],
				'value' => $entry['value'],
			];
			$this->mails->sendRequestMail($entry['request_id'], $user, $file, $recipient);
		}
	}
}
