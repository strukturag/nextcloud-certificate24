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

		$server = $this->config->getApiServer();
		if (empty($server)) {
			return;
		}

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedChildSrcDomain($server);
		$csp->addAllowedChildSrcDomain('blob:');
		$csp->addAllowedChildSrcDomain("'self'");
		$csp->addAllowedConnectDomain($server);
		$csp->addAllowedConnectDomain('blob:');
		$csp->addAllowedConnectDomain("'self'");
		$csp->addAllowedScriptDomain($server);
		$csp->addAllowedScriptDomain('blob:');
		$csp->addAllowedScriptDomain("'self'");
		$csp->addAllowedWorkerSrcDomain($server);
		$csp->addAllowedWorkerSrcDomain('blob:');
		$csp->addAllowedWorkerSrcDomain("'self'");
		$event->addPolicy($csp);
	}
}
