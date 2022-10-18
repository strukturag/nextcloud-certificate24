<?php

declare(strict_types=1);

namespace OCA\Esig\Notification;

use OCA\Esig\AppInfo\Application;
use OCA\Esig\Requests;
use OCP\Comments\NotFoundException;
use OCP\HintException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IAction;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\RichObjectStrings\Definitions;

class Notifier implements INotifier {
	protected IFactory $lFactory;
	protected IURLGenerator $url;
	protected IUserManager $userManager;
	protected INotificationManager $notificationManager;
	protected Definitions $definitions;
	protected Requests $requests;

	public function __construct(IFactory $lFactory,
								IURLGenerator $url,
								IUserManager $userManager,
								INotificationManager $notificationManager,
								Definitions $definitions,
								Requests $requests) {
		$this->lFactory = $lFactory;
		$this->url = $url;
		$this->userManager = $userManager;
		$this->notificationManager = $notificationManager;
		$this->definitions = $definitions;
		$this->requests = $requests;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'esig';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->lFactory->get(Application::APP_ID)->t('eSignatures');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 * @since 9.0.0
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			throw new \InvalidArgumentException('Incorrect app');
		}

		$userId = $notification->getUser();
		$user = $this->userManager->get($userId);
		if (!$user instanceof IUser) {
			throw new AlreadyProcessedException();
		}

		$l = $this->lFactory->get(Application::APP_ID, $languageCode);
		$parameters = $notification->getSubjectParameters();

		$request = $this->requests->getRequestById($parameters['request_id']);
		if (!$request) {
			// Request no longer exists.
			throw new AlreadyProcessedException();
		}

		$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath(Application::APP_ID, 'app-dark.svg')));
		$subject = $notification->getSubject();
		if ($subject === 'share') {
			return $this->parseShare($notification, $l);
		}

		$this->notificationManager->markProcessed($notification);
		throw new \InvalidArgumentException('Unknown subject');
	}

	/**
	 * @throws HintException
	 */
	protected function parseShare(INotification $notification, IL10N $l): INotification {
		$parameters = $notification->getSubjectParameters();
		$notification
			->setLink($this->url->linkToRouteAbsolute('esig.Page.index') . '#incoming-' . $parameters['request_id']);

		$message = $l->t('{user} requested your signature of "{filename}"');

		$uid = $parameters['sender'];
		$rosParameters = [
			'user' => [
				'type' => 'user',
				'id' => $uid,
				'name' => $this->userManager->getDisplayName($uid) ?? $uid,
			],
			'filename' => [
				'type' => 'highlight',
				'id' => $parameters['filename'],
				'name' => $parameters['filename'],
			],
		];

		$placeholders = $replacements = [];
		foreach ($rosParameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder .'}';
			if ($parameter['type'] === 'user') {
				$replacements[] = '@' . $parameter['name'];
			} else {
				$replacements[] = $parameter['name'];
			}
		}

		$notification->setParsedSubject(str_replace($placeholders, $replacements, $message));
		$notification->setRichSubject($message, $rosParameters);

		return $notification;
	}

}
