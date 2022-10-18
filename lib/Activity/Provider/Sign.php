<?php

declare(strict_types=1);

namespace OCA\Esig\Activity\Provider;

use OCP\Activity\IEvent;
use OCP\IL10N;

class Sign extends Base {

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

		if ($event->getSubject() === 'sign') {
			$l = $this->languageFactory->get('esig', $language);
			$parameters = $event->getSubjectParameters();

			$result = $this->parseSign($event, $l);
			$this->setSubjects($event, $result['subject'], $result['params']);
		} else {
			throw new \InvalidArgumentException('Wrong subject');
		}

		return $event;
	}

	protected function parseSign(IEvent $event, IL10N $l): array {
		$parameters = $event->getSubjectParameters();
		$subject = $l->t('The file "{filename}" was signed by {user}');
		$params = [
			'filename' => $this->getHighlight($parameters['filename']),
		];

		switch ($parameters['recipient_type']) {
			case 'user':
				$params['user'] = $this->getUser($parameters['recipient']);
				break;
			case 'email':
				$params['user'] = $this->getHighlight($parameters['recipient']);
				break;
		}

		return [
			'subject' => $subject,
			'params' => $params,
		];
	}
}
