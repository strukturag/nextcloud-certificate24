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
		} elseif ($event->getSubject() === 'last_signature') {
			$l = $this->languageFactory->get('esig', $language);
			$parameters = $event->getSubjectParameters();

			$result = $this->parseLastSignature($event, $l);
			$this->setSubjects($event, $result['subject'], $result['params']);
		} else {
			throw new \InvalidArgumentException('Wrong subject');
		}

		return $event;
	}

	protected function parseSign(IEvent $event, IL10N $l): array {
		$parameters = $event->getSubjectParameters();
		$params = [
			'filename' => $this->getHighlight($parameters['filename']),
		];
		if ($parameters['recipient_type'] === 'user' && $event->getAffectedUser() === $parameters['recipient']) {
			$subject = $l->t('You signed the file "{filename}"');
		} else {
			$subject = $l->t('The file "{filename}" was signed by {user}');

			switch ($parameters['recipient_type']) {
				case 'user':
					$params['user'] = $this->getUser($parameters['recipient']);
					break;
				case 'email':
					$params['user'] = $this->getHighlight($parameters['recipient']);
					break;
			}
		}

		return [
			'subject' => $subject,
			'params' => $params,
		];
	}

	protected function parseLastSignature(IEvent $event, IL10N $l): array {
		$parameters = $event->getSubjectParameters();
		$request = $parameters['request'];
		$subject = $l->t('The file "{filename}" was signed by all recipients');
		$params = [
			'filename' => $this->getHighlight($request['filename']),
		];

		return [
			'subject' => $subject,
			'params' => $params,
		];
	}
}
