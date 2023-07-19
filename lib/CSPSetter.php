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

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

/**
 * @template-implements IEventListener<Event>
 */
class CSPSetter implements IEventListener {
	protected IInitialState $initialState;
	protected Config $config;

	public function __construct(IInitialState $initialState,
		Config $config) {
		$this->initialState = $initialState;
		$this->config = $config;
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addServiceListener(AddContentSecurityPolicyEvent::class, self::class);
	}

	public function handle(Event $event): void {
		if (!($event instanceof AddContentSecurityPolicyEvent)) {
			return;
		}

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedChildSrcDomain('blob:');
		$csp->addAllowedChildSrcDomain("'self'");
		$csp->addAllowedConnectDomain('blob:');
		$csp->addAllowedConnectDomain("'self'");
		$csp->addAllowedScriptDomain('blob:');
		$csp->addAllowedScriptDomain("'self'");
		$csp->addAllowedWorkerSrcDomain('blob:');
		$csp->addAllowedWorkerSrcDomain("'self'");

		$apiServer = $this->config->getApiServer();
		if (!empty($apiServer)) {
			$csp->addAllowedChildSrcDomain($apiServer);
			$csp->addAllowedConnectDomain($apiServer);
			$csp->addAllowedScriptDomain($apiServer);
			$csp->addAllowedWorkerSrcDomain($apiServer);
		}

		$webServer = $this->config->getWebServer();
		if (!empty($webServer) && $apiServer !== $webServer) {
			$csp->addAllowedChildSrcDomain($webServer);
			$csp->addAllowedConnectDomain($webServer);
			$csp->addAllowedScriptDomain($webServer);
			$csp->addAllowedWorkerSrcDomain($webServer);
		}

		$event->addPolicy($csp);
	}
}
