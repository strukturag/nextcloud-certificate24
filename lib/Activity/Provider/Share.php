<?php

declare(strict_types=1);

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
