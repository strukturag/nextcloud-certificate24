<?php

declare(strict_types=1);

namespace OCA\Esig;

use OC\Template\Base as TemplateBase;
use OC\Template\TemplateFileLocator;
use OC_App;
use OC_Util;
use OCP\Defaults;
use OCP\IL10N;
use OCP\Util;

// Expose functions "p", "print_unescaped", etc. in email templates.
require_once \OC::$SERVERROOT.'/lib/private/legacy/template/functions.php';

class TranslatedTemplate extends TemplateBase {

	/**
	 * @param string $app
	 * @param string $name
	 * @param IL10N $l10n
	 */
	public function __construct(string $app, string $name, IL10N $l10n) {
		$theme = OC_Util::getTheme();
		list($path, $template) = $this->findTemplate($theme, $app, $name);

		$requestToken = \OC::$server->getSession() ? Util::callRegister() : '';

		/** @var Defaults $themeDefaults */
		$themeDefaults = \OC::$server->query(Defaults::class);
		parent::__construct($template, $requestToken, $l10n, $themeDefaults);
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
		$path = $locator->getPath();
		return array($path, $template);
	}

}
