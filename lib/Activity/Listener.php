<?php

declare(strict_types=1);

namespace OCA\Esig\Activity;

use OCA\Esig\Events\ShareEvent;
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

	public function __construct(IManager $activityManager,
								IUserSession $userSession,
								LoggerInterface $logger,
								ITimeFactory $timeFactory) {
		$this->activityManager = $activityManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
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
}
