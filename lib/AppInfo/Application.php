<?php

declare(strict_types=1);

namespace OCA\Esig\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
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

		Util::addScript('esig', 'esig-loader');
	}

}
