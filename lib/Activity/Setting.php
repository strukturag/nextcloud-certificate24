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
namespace OCA\Esig\Activity;

use OCP\Activity\ActivitySettings;
use OCP\IL10N;

class Setting extends ActivitySettings {
	protected IL10N $l;

	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	/**
	 * @return string Lowercase a-z and underscore only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier(): string {
		return 'esig';
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName(): string {
		return $this->l->t('A file was shared with you for signing or a file was signed');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGroupIdentifier(): string {
		return 'other';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGroupName() {
		return $this->l->t('Other activities');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority(): int {
		return 51;
	}
	/**
	 * {@inheritdoc}
	 */
	public function canChangeNotification(): bool {
		return false;
	}
	/**
	 * {@inheritdoc}
	 */
	public function isDefaultEnabledNotification(): bool {
		return false;
	}
}
