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
namespace OCA\Certificate24;

use OCA\Certificate24\AppInfo\Application;
use OCA\Files\Event\LoadSidebar;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @template-implements IEventListener<Event>
 */
class FilesLoader implements IEventListener {
	protected IInitialState $initialState;
	protected IAppManager $appManager;
	protected Config $config;

	public function __construct(IInitialState $initialState,
		IAppManager $appManager,
		Config $config) {
		$this->initialState = $initialState;
		$this->appManager = $appManager;
		$this->config = $config;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addServiceListener(LoadAdditionalScriptsEvent::class, self::class);
		$dispatcher->addServiceListener(LoadSidebar::class, self::class);
	}

	public function handle(Event $event): void {
		if ($event instanceof LoadAdditionalScriptsEvent) {
			$this->handleAdditionalScripts($event);
		}
		if ($event instanceof LoadSidebar) {
			$this->handleSidebar($event);
		}
	}

	private function setupInitialState(string $server) {
		if (empty($server)) {
			return;
		}

		$this->initialState->provideInitialState(
			'vinegar_server',
			$server
		);

		$this->initialState->provideInitialState(
			'public-settings',
			[
				'signed_save_mode' => $this->config->getSignedSaveMode(),
			]
		);
	}

	private function handleAdditionalScripts(LoadAdditionalScriptsEvent $event): void {
		if (!$this->appManager->isEnabledForUser(Application::APP_ID)) {
			return;
		}

		$server = $this->config->getApiServer();
		if (empty($server)) {
			return;
		}

		$this->setupInitialState($server);
		Util::addScript(Application::APP_ID, Application::APP_ID . '-loader');
		Util::addStyle(Application::APP_ID, 'icons');
	}

	private function handleSidebar(LoadSidebar $event): void {
		$server = $this->config->getApiServer();
		if (empty($server)) {
			return;
		}

		$this->setupInitialState($server);
		Util::addScript(Application::APP_ID, Application::APP_ID . '-files-sidebar');
		Util::addStyle(Application::APP_ID, 'icons');
	}
}
