<?php

declare(strict_types=1);

namespace OCA\Esig\AppInfo;

use OCA\Esig\Activity\Listener as ActivityListener;
use OCA\Esig\Config;
use OCA\Esig\Notification\Listener as NotificationListener;
use OCA\Esig\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Util;

class Application extends App implements IBootstrap {
	public const APP_ID = 'esig';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		// Register the composer autoloader for packages shipped by this app
		include_once __DIR__ . '/../../vendor/autoload.php';
	}

	public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();

		$server->getNavigationManager()->add(function () use ($server) {
			/** @var Config $config */
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
		Util::addScript('esig', 'esig-loader');

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $server->get(IEventDispatcher::class);

		/*
		$config = $server->get(Config::class);
		$serverUrl = $config->getServer();
		if ($serverUrl) {
			$dispatcher->addListener(AddContentSecurityPolicyEvent::class, function (AddContentSecurityPolicyEvent $e) use ($serverUrl) {
				$csp = new ContentSecurityPolicy();
				$csp->addAllowedConnectDomain($serverUrl);
				$csp->addAllowedScriptDomain($serverUrl);
				$e->addPolicy($csp);
			});
		}
		*/

		ActivityListener::register($dispatcher);
		NotificationListener::register($dispatcher);

		$manager = $server->getNotificationManager();
		$manager->registerNotifierService(Notifier::class);
	}

}
