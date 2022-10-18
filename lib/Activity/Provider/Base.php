<?php

declare(strict_types=1);

namespace OCA\Esig\Activity\Provider;

use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
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
