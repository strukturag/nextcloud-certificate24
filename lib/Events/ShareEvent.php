<?php

declare(strict_types=1);

namespace OCA\Esig\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\File;
use OCP\IUser;

class ShareEvent extends Event {

	private File $file;
	private IUser $user;
	private string $recipient;
	private string $recipient_type;
	private string $request_id;

	public function __construct(File $file, IUser $user, string $recipient, string $recipient_type, string $request_id) {
		parent::__construct();
		$this->file = $file;
		$this->user = $user;
		$this->recipient = $recipient;
		$this->recipient_type = $recipient_type;
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
	 * @return string
	 */
	public function getRecipient(): string {
		return $this->recipient;
	}

	/**
	 * @return string
	 */
	public function getRecipientType(): string {
		return $this->recipient_type;
	}

	/**
	 * @return string
	 */
	public function getRequestId(): string {
		return $this->request_id;
	}

}
