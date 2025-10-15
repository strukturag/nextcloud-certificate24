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
namespace OCA\Certificate24\AppInfo;

use OCA\Certificate24\Activity\Listener as ActivityListener;
use OCA\Certificate24\Capabilities;
use OCA\Certificate24\CSPSetter;
use OCA\Certificate24\Dashboard\Certificate24Widget;
use OCA\Certificate24\DeleteListener;
use OCA\Certificate24\FilesLoader;
use OCA\Certificate24\Manager;
use OCA\Certificate24\Notification\Listener as NotificationListener;
use OCA\Certificate24\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\INavigationManager;
use OCP\IUser;

class Application extends App implements IBootstrap {
	public const APP_ID = 'certificate24';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerDashboardWidget(Certificate24Widget::class);
	}

	public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();

		$server->get(INavigationManager::class)->add(function () use ($server) {
			/** @var IUser $user */
			$user = $server->getUserSession()->getUser();
			return [
				'id' => self::APP_ID,
				'name' => $server->getL10N(self::APP_ID)->t('Certificate24'),
				'href' => $server->getURLGenerator()->linkToRouteAbsolute(self::APP_ID . '.Page.index'),
				'icon' => $server->getURLGenerator()->imagePath(self::APP_ID, 'app.svg'),
				'order' => 3,
				'type' => 'link',
			];
		});

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $server->get(IEventDispatcher::class);

		ActivityListener::register($dispatcher);
		CSPSetter::register($dispatcher);
		FilesLoader::register($dispatcher);
		NotificationListener::register($dispatcher);
		DeleteListener::register($dispatcher);
		Manager::register($dispatcher);

		$manager = $server->getNotificationManager();
		$manager->registerNotifierService(Notifier::class);
	}
}
