<?php

declare(strict_types=1);

namespace OCA\Esig;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Esig\Config;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Util;

class FilesLoader implements IEventListener {

	protected IInitialState $initialState;
	protected Config $config;

	public function __construct(IInitialState $initialState,
								Config $config) {
		$this->initialState = $initialState;
		$this->config = $config;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addServiceListener(LoadAdditionalScriptsEvent::class, self::class);
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}

		$server = $this->config->getServer();
		if (empty($server)) {
			return;
		}

		$this->initialState->provideInitialState(
			'vinegar_server',
			$server
		);

		Util::addScript('esig', 'esig-loader');
	}

}
