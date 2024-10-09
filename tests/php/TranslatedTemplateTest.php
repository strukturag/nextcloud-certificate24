<?php

/**
 * @copyright Copyright (c) 2024, struktur AG.
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
namespace OCA\Certificate24\Tests\php;

use OCA\Certificate24\AppInfo\Application;
use OCA\Certificate24\TranslatedTemplate;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TranslatedTemplateTest extends TestCase {
	/** @var MockObject|IFactory $l10nFactory */
	protected IFactory $l10nFactory;

	public function setUp(): void {
		parent::setUp();

		$this->l10nFactory = \OC::$server->query(IFactory::class);
	}

	public function testTranslatedTemplate() {
		$l10nEN = $this->l10nFactory->get(Application::APP_ID, 'en');
		$template1 = new TranslatedTemplate(Application::APP_ID, 'email.share.subject', $l10nEN);
		$result1 = $template1->fetchPage();

		$l10nDE = $this->l10nFactory->get(Application::APP_ID, 'de');
		$template2 = new TranslatedTemplate(Application::APP_ID, 'email.share.subject', $l10nDE);
		$result2 = $template2->fetchPage();

		$this->assertNotEquals($result1, $result2);
	}
}
