<?php

declare(strict_types=1);

namespace OCA\Esig\AppInfo;

use OCA\Esig\Activity\Listener as ActivityListener;
use OCA\Esig\Capabilities;
use OCA\Esig\CSPSetter;
use OCA\Esig\DeleteListener;
use OCA\Esig\FilesLoader;
use OCA\Esig\Manager;
use OCA\Esig\Notification\Listener as NotificationListener;
use OCA\Esig\Notification\Notifier;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
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

		// Register the composer autoloader for packages shipped by this app
		include_once __DIR__ . '/../../vendor/autoload.php';
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
