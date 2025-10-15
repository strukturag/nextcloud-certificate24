<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use OCP\App\IAppManager;
use OCP\Server;

if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}
if (file_exists(__DIR__ . '/../../../nextcloud/lib/base.php')) {
	require_once __DIR__ . '/../../../nextcloud/lib/base.php';
} else {
	require_once __DIR__ . '/../../../../lib/base.php';
}

if (file_exists(__DIR__ . '/../../../nextcloud/tests/autoload.php')) {
	require_once __DIR__ . '/../../../nextcloud/tests/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../../tests/autoload.php')) {
	require_once __DIR__ . '/../../../../tests/autoload.php';
} else {
	\OC::$loader->addValidRoot(\OC::$SERVERROOT . '/tests');
	if (!class_exists('\PHPUnit\Framework\TestCase')) {
		require_once('PHPUnit/Autoload.php');
	}
}

Server::get(IAppManager::class)->loadApp('spreed');
OC_Hook::clear();
