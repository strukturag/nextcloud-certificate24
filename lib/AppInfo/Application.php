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
namespace OCA\Esig\AppInfo;

use OCA\Esig\Activity\Listener as ActivityListener;
use OCA\Esig\Capabilities;
use OCA\Esig\CSPSetter;
use OCA\Esig\Dashboard\EsigWidget;
use OCA\Esig\DeleteListener;
use OCA\Esig\FilesLoader;
use OCA\Esig\Manager;
use OCA\Esig\Notification\Listener as NotificationListener;
use OCA\Esig\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'esig';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(AddContentSecurityPolicyEvent::class, CSPSetter::class);
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, FilesLoader::class);
		$context->registerCapability(Capabilities::class);
		$context->registerDashboardWidget(EsigWidget::class);
	}

	public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();

		$server->getNavigationManager()->add(function () use ($server) {
			/** @var IUser $user */
			$user = $server->getUserSession()->getUser();
			return [
				'id' => self::APP_ID,
				'name' => $server->getL10N(self::APP_ID)->t('eSignatures'),
				'href' => $server->getURLGenerator()->linkToRouteAbsolute('esig.Page.index'),
				'icon' => $server->getURLGenerator()->imagePath(self::APP_ID, 'app.svg'),
				'order' => 3,
				'type' => 'link',
			];
		});

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $server->get(IEventDispatcher::class);

		ActivityListener::register($dispatcher);
		NotificationListener::register($dispatcher);
		DeleteListener::register($dispatcher);
		Manager::register($dispatcher);

		$manager = $server->getNotificationManager();
		$manager->registerNotifierService(Notifier::class);
	}
}
