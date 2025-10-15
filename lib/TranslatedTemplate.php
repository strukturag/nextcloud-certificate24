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

use OC\Template\Base as TemplateBase;
use OC\Template\TemplateFileLocator;
use OC_App;
use OC_Util;
use OCP\Defaults;
use OCP\IL10N;
use OCP\Util;

// Expose functions "p", "print_unescaped", etc. in email templates.
if (file_exists(\OC::$SERVERROOT . '/lib/private/legacy/template/functions.php')) {
	require_once \OC::$SERVERROOT . '/lib/private/legacy/template/functions.php';
} else {
	// Nextcloud 32+
	require_once \OC::$SERVERROOT . '/lib/private/Template/functions.php';
}

class TranslatedTemplate extends TemplateBase {
	/**
	 * @param string $app
	 * @param string $name
	 * @param IL10N $l10n
	 */
	public function __construct(string $app, string $name, IL10N $l10n) {
		$theme = OC_Util::getTheme();
		[$path, $template] = $this->findTemplate($theme, $app, $name);

		$requestToken = \OC::$server->getSession() ? Util::callRegister() : '';
		$cspNonce = \OCP\Server::get(\OC\Security\CSP\ContentSecurityPolicyNonceManager::class)->getNonce();

		/** @var Defaults $themeDefaults */
		$themeDefaults = \OC::$server->query(Defaults::class);
		parent::__construct($template, $requestToken, $l10n, $themeDefaults, $cspNonce);
	}

	/**
	 * find the template with the given name
	 * @param string $name of the template file (without suffix)
	 *
	 * Will select the template file for the selected theme.
	 * Checking all the possible locations.
	 * @param string $theme
	 * @param string $app
	 * @return string[]
	 */
	protected function findTemplate($theme, $app, $name) {
		// Check if it is a app template or not.
		if ($app !== '') {
			$dirs = $this->getAppTemplateDirs($theme, $app, \OC::$SERVERROOT, OC_App::getAppPath($app));
		} else {
			$dirs = $this->getCoreTemplateDirs($theme, \OC::$SERVERROOT);
		}
		$locator = new TemplateFileLocator($dirs);
		$template = $locator->find($name);
		if (is_array($template)) {
			// Nextcloud 32+ returns [$path, $template] directly.
			return $template;
		}

		$path = $locator->getPath();
		return [$path, $template];
	}
}
