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
			if ($event->getRecipientType() !== 'user') {
				// Only generate activities for users.
				return;
			}

			$listener = Server::get(self::class);
			$listener->onShareEvent($event);
		};
		$dispatcher->addListener(ShareEvent::class, $listener);

		$listener = static function (SignEvent $event): void {
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
				->setType('esig')
				->setAuthor($sender->getUID())
				->setObject('file', $file->getId())
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

		try {
			$activity->setAffectedUser($event->getRecipient());
			$this->activityManager->publish($activity);
		} catch (\BadMethodCallException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		}

		return true;
	}

	/**
	 * The file "{filename}" was signed by {user}"
	 *
	 * @param ShareEvent $event
	 * @return bool True if activity was generated, false otherwise
	 */
	public function onSignEvent(SignEvent $event): bool {
		$id = $event->getRequestId();
		$request = $event->getRequest();

		$activity = $this->activityManager->generateEvent();
		try {
			$activity->setApp('esig')
				->setType('esig')
				->setObject('file', (int) $request['file_id'])
				->setTimestamp($this->timeFactory->getTime())
				->setAffectedUser($request['user_id'])
				->setSubject('sign', [
					'file_id' => $request['file_id'],
					'filename' => $request['filename'],
					'recipient' => $request['recipient'],
					'recipient_type' => $request['recipient_type'],
					'request_id' => $id,
				]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return false;
		}

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

		return true;
	}

}
