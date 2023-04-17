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
namespace OCA\Esig\Activity\Provider;

use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;

abstract class Base implements IProvider {
	protected IFactory $languageFactory;
	protected IURLGenerator $urlGenerator;
	protected IManager $activityManager;
	protected IUserManager $userManager;

	public function __construct(IFactory $languageFactory,
		IURLGenerator $urlGenerator,
		IManager $activityManager,
		IUserManager $userManager) {
		$this->languageFactory = $languageFactory;
		$this->urlGenerator = $urlGenerator;
		$this->activityManager = $activityManager;
		$this->userManager = $userManager;
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 */
	public function preParse(IEvent $event): IEvent {
		if ($event->getApp() !== 'esig') {
			throw new \InvalidArgumentException('Wrong app');
		}

		$user = $this->userManager->get($event->getAffectedUser());
		if (!$user instanceof IUser) {
			throw new \InvalidArgumentException('User can not use app');
		}

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('esig', 'app-dark.png')));
		} else {
			$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('esig', 'app-dark.svg')));
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param string $subject
	 * @param array $parameters
	 * @throws \InvalidArgumentException
	 */
	protected function setSubjects(IEvent $event, string $subject, array $parameters): void {
		$placeholders = $replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			$replacements[] = $parameter['name'];
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
	}

	protected function getUser(string $uid): array {
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->userManager->getDisplayName($uid) ?? $uid,
		];
	}

	protected function getHighlight(string $text): array {
		return [
			'type' => 'highlight',
			'id' => $text,
			'name' => $text,
		];
	}
}
