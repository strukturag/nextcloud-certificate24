<?php

declare(strict_types=1);

namespace OCA\Esig\Activity;

use OCA\Esig\Events\ShareEvent;
use OCA\Esig\Events\SignEvent;
use OCA\Esig\Requests;
use OCP\Activity\IManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;
use Psr\Log\LoggerInterface;

class Listener {
	protected IManager $activityManager;

	protected IUserSession $userSession;

	protected LoggerInterface $logger;

	protected ITimeFactory $timeFactory;

	protected Requests $requests;

	public function __construct(IManager $activityManager,
								IUserSession $userSession,
								LoggerInterface $logger,
								ITimeFactory $timeFactory,
								Requests $requests) {
		$this->activityManager = $activityManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
		$this->requests = $requests;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$listener = static function (ShareEvent $event): void {
			/** @var Listener $listener */
			$listener = Server::get(self::class);
			$listener->onShareEvent($event);
		};
		$dispatcher->addListener(ShareEvent::class, $listener);

		$listener = static function (SignEvent $event): void {
			/** @var Listener $listener */
			$listener = Server::get(self::class);
			$listener->onSignEvent($event);
		};
		$dispatcher->addListener(SignEvent::class, $listener);
	}

	/**
	 * "{user} requested your signature on {file}"
	 *
	 * @param ShareEvent $event
	 * @return bool True if activity was generated, false otherwise
	 */
	public function onShareEvent(ShareEvent $event): bool {
		$sender = $event->getUser();
		$file = $event->getFile();
		$activity = $this->activityManager->generateEvent();
		try {
			$activity->setApp('esig')
				->setType('incoming_request')
				->setAuthor($sender->getUID())
				->setObject('incoming_request', 0, $event->getRequestId())
				->setTimestamp($this->timeFactory->getTime())
				->setSubject('share', [
					'file_id' => $file->getId(),
					'filename' => $file->getName(),
					'sender' => $sender->getUID(),
					'request_id' => $event->getRequestId(),
				]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return false;
		}

		foreach ($event->getRecipients() as $recipient) {
			$type = $recipient['type'];
			if ($type !== 'user') {
				continue;
			}

			try {
				$activity->setAffectedUser($recipient['value']);
				$this->activityManager->publish($activity);
			} catch (\BadMethodCallException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			} catch (\InvalidArgumentException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}
		}

		return true;
	}

	/**
	 * The file "{filename}" was signed by {user}"
	 *
	 * @param SignEvent $event
	 * @return bool True if activity was generated, false otherwise
	 */
	public function onSignEvent(SignEvent $event): bool {
		$id = $event->getRequestId();
		$request = $event->getRequest();

		if ($event->getRecipientType() === 'user' && $event->getRecipient() !== $request['user_id']) {
			// Add activity for sender that the recipient has signed (only if not requested from themselves).
			$activity = $this->activityManager->generateEvent();
			try {
				$activity->setApp('esig')
					->setType('recipient_signed')
					->setObject('outgoing_request', 0, $id)
					->setTimestamp($event->getSigned()->getTimestamp())
					->setAffectedUser($request['user_id'])
					->setSubject('sign', [
						'file_id' => $request['file_id'],
						'filename' => $request['filename'],
						'recipient' => $event->getRecipient(),
						'recipient_type' => $event->getRecipientType(),
						'request_id' => $id,
					]);
			} catch (\InvalidArgumentException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
				$activity = null;
			}

			if ($activity) {
				$user = $event->getUser();
				if ($user) {
					$activity->setAuthor($user->getUID());
				}

				try {
					$this->activityManager->publish($activity);
				} catch (\BadMethodCallException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				} catch (\InvalidArgumentException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				}
			}
		}

		if ($event->getRecipientType() === 'user') {
			// Add activity for recipient that they have signed.
			$activity = $this->activityManager->generateEvent();
			try {
				$activity->setApp('esig')
					->setType('own_signed')
					->setObject('incoming_request', 0, $id)
					->setTimestamp($event->getSigned()->getTimestamp())
					->setAffectedUser($event->getRecipient())
					->setSubject('sign', [
						'file_id' => $request['file_id'],
						'filename' => $request['filename'],
						'recipient' => $event->getRecipient(),
						'recipient_type' => $event->getRecipientType(),
						'request_id' => $id,
					]);
			} catch (\InvalidArgumentException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
				$activity = null;
			}

			if ($activity) {
				$user = $event->getUser();
				if ($user) {
					$activity->setAuthor($user->getUID());
				}

				try {
					$this->activityManager->publish($activity);
				} catch (\BadMethodCallException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				} catch (\InvalidArgumentException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				}
			}
		}

		if ($event->isLastSignature()) {
			foreach ($request['recipients'] as $recipient) {
				$type = $recipient['type'];
				if ($type !== 'user') {
					continue;
				}

				$activity = $this->activityManager->generateEvent();
				try {
					$activity->setApp('esig')
						->setType('finished_request')
						->setObject('finished_request', 0, $id)
						->setTimestamp($event->getSigned()->getTimestamp())
						->setAffectedUser($recipient['value'])
						->setSubject('last_signature', [
							'request' => $request,
							'request_id' => $id,
						]);
				} catch (\InvalidArgumentException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
					continue;
				}

				try {
					$this->activityManager->publish($activity);
				} catch (\BadMethodCallException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				} catch (\InvalidArgumentException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				}
			}
		}

		return true;
	}

}
