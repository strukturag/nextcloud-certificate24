<?php

declare(strict_types=1);

namespace OCA\Esig\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

class SignEvent extends Event {
	private string $request_id;
	private array $request;
	private string $type;
	private string $value;
	private \DateTime $signed;
	private ?IUser $user;
	private bool $lastSignature;

	public function __construct(string $request_id, array $request, string $type, string $value, \DateTime $signed, ?IUser $user, bool $lastSignature) {
		parent::__construct();
		$this->request_id = $request_id;
		$this->request = $request;
		$this->type = $type;
		$this->value = $value;
		$this->signed = $signed;
		$this->user = $user;
		$this->lastSignature = $lastSignature;
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
	 * @return string
	 */
	public function getRecipientType(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getRecipient(): string {
		return $this->value;
	}

	/**
	 * @return \DateTime
	 */
	public function getSigned(): \DateTime {
		return $this->signed;
	}

	/**
	 * The user that performed the signature or null if signed anonymously.
	 *
	 * @return ?IUser
	 */
	public function getUser(): ?IUser {
		return $this->user;
	}

	/**
	 * @return bool
	 */
	public function isLastSignature(): bool {
		return $this->lastSignature;
	}
}
