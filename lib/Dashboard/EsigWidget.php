<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, struktur AG.
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
namespace OCA\Esig\Dashboard;

use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

class EsigWidget implements IAPIWidget, IIconWidget, IButtonWidget, IOptionWidget {

	private IURLGenerator $url;
	private IL10N $l10n;

	public function __construct(
		IURLGenerator $url,
		IL10N $l10n
	) {
		$this->url = $url;
		$this->l10n = $l10n;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'esig';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Signature requests');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconClass(): string {
		return 'dashboard-esig-icon';
	}

	public function getWidgetOptions(): WidgetOptions {
		return new WidgetOptions(true);
	}

	/**
	 * @return \OCP\Dashboard\Model\WidgetButton[]
	 */
	public function getWidgetButtons(string $userId): array {
		$buttons = [];
		$buttons[] = new WidgetButton(
			WidgetButton::TYPE_MORE,
			$this->url->linkToRouteAbsolute('esig.Page.index'),
			$this->l10n->t('More signature requests')
		);
		return $buttons;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->url->getAbsoluteURL($this->url->imagePath('esig', 'app-dark.svg'));
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl(): ?string {
		return $this->url->linkToRouteAbsolute('esig.Page.index');
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		Util::addStyle('esig', 'icons');
		Util::addScript('esig', 'esig-dashboard');
	}

	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		return [];
	}

}
