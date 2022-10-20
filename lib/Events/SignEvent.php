<?php

declare(strict_types=1);

namespace OCA\Esig\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

class SignEvent extends Event {

	private string $request_id;
	private array $request;
	private ?IUser $user;

	public function __construct(string $request_id, array $request, ?IUser $user) {
		parent::__construct();
		$this->request_id = $request_id;
		$this->request = $request;
		$this->user = $user;
	}

	/**
	 * @return string
	 */
	public function getRequestId(): string {
		return $this->request_id;
	}

	/**
	 * @return array
	 */
	public function getRequest(): array {
		return $this->request;
	}

	/**
	 * @return ?IUser
	 */
	public function getUser(): ?IUser {
		return $this->user;
	}

}
