<?php

declare(strict_types=1);

namespace OCA\Esig\Events;

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
