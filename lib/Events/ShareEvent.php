<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, struktur AG.
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
namespace OCA\Certificate24\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\File;
use OCP\IUser;

class ShareEvent extends Event {
	private File $file;
	private IUser $user;
	private array $recipients;
	private string $request_id;

	public function __construct(File $file, IUser $user, array $recipients, string $request_id) {
		parent::__construct();
		$this->file = $file;
		$this->user = $user;
		$this->recipients = $recipients;
		$this->request_id = $request_id;
	}

	/**
	 * @return File
	 */
	public function getFile(): File {
		return $this->file;
	}

	/**
	 * @return IUser
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @return array
	 */
	public function getRecipients(): array {
		return $this->recipients;
	}

	/**
	 * @return string
	 */
	public function getRequestId(): string {
		return $this->request_id;
	}
}
