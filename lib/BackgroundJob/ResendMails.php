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
namespace OCA\Certificate24\BackgroundJob;

use OCA\Certificate24\Mails;
use OCA\Certificate24\Requests;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
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
				'display_name' => $entry['recipient_display_name'],
				'c24_signature_id' => $entry['c24_signature_id'],
			];
			$this->mails->sendRequestMail($entry['id'], $user, $file, $recipient, $entry['c24_server']);
		}

		foreach ($pending['multi'] as $entry) {
			$request = $entry['request'];
			$user = $this->userManager->get($request['user_id']);
			if (!$user) {
				// Should not happen, requests will get deleted if the owner is deleted.
				continue;
			}

			$files = $this->root->getUserFolder($user->getUID())->getById($request['file_id']);
			if (empty($files)) {
				// Should not happen, requests will get deleted if the associated file is deleted.
				continue;
			}

			$file = $files[0];
			$recipient = [
				'type' => $entry['type'],
				'value' => $entry['value'],
				'display_name' => $entry['display_name'],
				'c24_signature_id' => $entry['c24_signature_id'],
			];
			$this->mails->sendRequestMail($entry['request_id'], $user, $file, $recipient, $request['c24_server']);
		}
	}
}
