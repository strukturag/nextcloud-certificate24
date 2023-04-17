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
use OCP\IL10N;

class Share extends Base {
	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, IEvent $previousEvent = null): IEvent {
		$event = $this->preParse($event);

		if ($event->getSubject() === 'share') {
			$l = $this->languageFactory->get('esig', $language);
			$parameters = $event->getSubjectParameters();

			$url = $this->urlGenerator->linkToRouteAbsolute('esig.Page.index') . '#incoming-' . $parameters['request_id'];
			$event->setLink($url);

			$result = $this->parseShare($event, $l);
			$this->setSubjects($event, $result['subject'], $result['params']);
		} else {
			throw new \InvalidArgumentException('Wrong subject');
		}

		return $event;
	}

	protected function parseShare(IEvent $event, IL10N $l): array {
		$parameters = $event->getSubjectParameters();
		$subject = $l->t('{user} requested your signature of "{filename}"');
		$params = [
			'user' => $this->getUser($parameters['sender']),
			'filename' => $this->getHighlight($parameters['filename']),
		];

		return [
			'subject' => $subject,
			'params' => $params,
		];
	}
}
