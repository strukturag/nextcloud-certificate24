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
namespace OCA\Esig\Notification;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Events\ShareEvent;
use OCA\Esig\Events\SignEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Notification\IManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event>
 */
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
	 */
	public function onShareEvent(ShareEvent $event): void {
		$sender = $event->getUser();
		$file = $event->getFile();

		$shouldFlush = $this->notificationManager->defer();
		$dateTime = $this->timeFactory->getDateTime();
		$hasNotifications = false;
		foreach ($event->getRecipients() as $recipient) {
			$type = $recipient['type'];
			if ($type !== 'user') {
				continue;
			}

			$notification = $this->notificationManager->createNotification();
			try {
				$notification->setApp(Application::APP_ID)
					->setDateTime($dateTime)
					->setUser($recipient['value'])
					->setObject('incoming_request', $event->getRequestId())
					->setSubject('share', [
						'file_id' => $file->getId(),
						'filename' => $file->getName(),
						'sender' => $sender->getUID(),
						'request_id' => $event->getRequestId(),
					]);
				$this->notificationManager->notify($notification);
				$hasNotifications = true;
			} catch (\InvalidArgumentException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}
		}
		if ($hasNotifications && $shouldFlush) {
			$this->notificationManager->flush();
		}
	}

	/**
	 * "The file "{filename}" was signed by {user}"
	 *
	 * @param SignEvent $event
	 */
	public function onSignEvent(SignEvent $event): void {
		$request = $event->getRequest();
		$user = $event->getUser();

		$notification = $this->notificationManager->createNotification();
		$shouldFlush = $this->notificationManager->defer();
		try {
			$notification->setApp(Application::APP_ID)
				->setDateTime($event->getSigned())
				->setUser($request['user_id'])
				->setObject('outgoing_request', $event->getRequestId())
				->setSubject('sign', [
					'request' => $request,
					'request_id' => $event->getRequestId(),
					'recipient' => $event->getRecipient(),
					'recipient_type' => $event->getRecipientType(),
					'user' => $user ? $user->getUID() : null,
				]);
			$this->notificationManager->notify($notification);
		} catch (\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		}

		if ($event->isLastSignature()) {
			$notified = [];
			foreach ($request['recipients'] as $recipient) {
				if ($recipient['type'] === 'email') {
					continue;
				}

				$notification = $this->notificationManager->createNotification();
				try {
					$notification->setApp(Application::APP_ID)
						->setDateTime($event->getSigned())
						->setUser($recipient['value'])
						->setObject('finished_request', $event->getRequestId())
						->setSubject('last_signature', [
							'request' => $request,
							'request_id' => $event->getRequestId(),
						]);
					$this->notificationManager->notify($notification);
					$notified[$recipient['value']] = true;
				} catch (\InvalidArgumentException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				}
			}

			if (!isset($notified[$request['user_id']])) {
				$notification = $this->notificationManager->createNotification();
				try {
					$notification->setApp(Application::APP_ID)
						->setDateTime($event->getSigned())
						->setUser($request['user_id'])
						->setObject('finished_request', $event->getRequestId())
						->setSubject('last_signature', [
							'request' => $request,
							'request_id' => $event->getRequestId(),
						]);
					$this->notificationManager->notify($notification);
					$notified[$recipient['value']] = true;
				} catch (\InvalidArgumentException $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				}
			}
		}
		if ($shouldFlush) {
			$this->notificationManager->flush();
		}
	}

	public function handle(Event $event): void {
		if ($event instanceof ShareEvent) {
			$this->onShareEvent($event);
		}
		if ($event instanceof SignEvent) {
			$this->onSignEvent($event);
		}
	}
}
