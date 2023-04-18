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
namespace OCA\Esig;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event>
 */
class DeleteListener implements IEventListener {
	protected LoggerInterface $logger;
	protected Requests $requests;
	protected Config $config;
	protected Manager $manager;

	public function __construct(LoggerInterface $logger,
		Requests $requests,
		Config $config,
		Manager $manager) {
		$this->logger = $logger;
		$this->requests = $requests;
		$this->config = $config;
		$this->manager = $manager;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addServiceListener(NodeDeletedEvent::class, self::class);
		$dispatcher->addServiceListener(UserDeletedEvent::class, self::class);
	}

	public function handle(Event $event): void {
		$account = $this->config->getAccount();
		if ($event instanceof UserDeletedEvent) {
			$user = $event->getUser();
			$requests = $this->requests->getOwnRequests($user, true);
			foreach ($requests as $request) {
				$this->manager->deleteRequest($account, $request);
			}

			$requests = $this->requests->getIncomingRequests($user, true);
			foreach ($requests as $request) {
				$this->manager->deleteRequest($account, $request);
			}
		}
		if ($event instanceof NodeDeletedEvent) {
			$file = $event->getNode();
			$requests = $this->requests->getRequestsForFile($file, true);
			foreach ($requests as $request) {
				$this->manager->deleteRequest($account, $request);
			}
		}
	}
}
