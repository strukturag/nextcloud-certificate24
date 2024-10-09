<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024, struktur AG.
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
namespace OCA\Certificate24\Events;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

if (interface_exists(\OCP\EventDispatcher\IWebhookCompatibleEvent::class)) {
	// Nextcloud 30 or newer.
	abstract class BaseEvent extends Event implements IWebhookCompatibleEvent {
	}
} else {
	// Compatibility for Nextcloud 29 or older.
	abstract class BaseEvent extends Event {

		/**
		 * Return data to be JSON serialized for a Webhook (requires Nextcloud 30).
		 */
		abstract public function getWebhookSerializable(): array;

	}
}
