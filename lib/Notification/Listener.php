<?php

declare(strict_types=1);

namespace OCA\Esig\Notification;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Events\ShareEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Notification\IManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class Listener implements IEventListener {

	protected IManager $notificationManager;
	protected IEventDispatcher $dispatcher;
	protected ITimeFactory $timeFactory;
	protected LoggerInterface $logger;

	public function __construct(IManager $notificationManager,
								IEventDispatcher $dispatcher,
								ITimeFactory $timeFactory,
								LoggerInterface $logger) {
		$this->notificationManager = $notificationManager;
		$this->dispatcher = $dispatcher;
		$this->timeFactory = $timeFactory;
		$this->logger = $logger;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$listener = static function (ShareEvent $event): void {
			$listener = Server::get(self::class);
			$listener->onShareEvent($event);
		};
		$dispatcher->addListener(ShareEvent::class, $listener);
	}

	/**
	 * "{user} requested your signature on {file}"
	 *
	 * @param ShareEvent $event
	 */
	public function onShareEvent(ShareEvent $event): void {
		if ($event->getRecipientType() !== 'user') {
			return;
		}

		$sender = $event->getUser();
		$file = $event->getFile();

		$notification = $this->notificationManager->createNotification();
		$shouldFlush = $this->notificationManager->defer();
		$dateTime = $this->timeFactory->getDateTime();
		try {
			$notification->setApp(Application::APP_ID)
				->setDateTime($dateTime)
				->setUser($event->getRecipient())
				->setObject('file', (string) $file->getId())
				->setSubject('share', [
					'file_id' => $file->getId(),
					'filename' => $file->getName(),
					'sender' => $sender->getUID(),
					'request_id' => $event->getRequestId(),
				]);
			$this->notificationManager->notify($notification);
		} catch (\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			if ($shouldFlush) {
				$this->notificationManager->flush();
			}
		}
	}

	public function handle(Event $event): void {
		if ($event instanceof ShareEvent) {
			$this->onShareEvent($event);
		}
	}

}
