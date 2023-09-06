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
namespace OCA\Certificate24\BackgroundJob;

use GuzzleHttp\Exception\ConnectException;
use OCA\Certificate24\Client;
use OCA\Certificate24\Config;
use OCA\Certificate24\Verify;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\IResult;
use OCP\Files\File;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class BackgroundVerify extends TimedJob {

	// Retry files with errors after 1 hour.
	private const VERIFY_ERROR_DELAY_MINUTES = 60;

	private LoggerInterface $logger;
	private IUserManager $userManager;
	private IDBConnection $db;
	private IRootFolder $rootFolder;
	private IMimeTypeLoader $mimeTypeLoader;
	private Config $config;
	private Client $client;
	private Verify $verify;

	public function __construct(ITimeFactory $timeFactory,
		LoggerInterface $logger,
		IUserManager $userManager,
		IDBConnection $db,
		IRootFolder $rootFolder,
		IMimeTypeLoader $mimeTypeLoader,
		Config $config,
		Client $client,
		Verify $verify) {
		parent::__construct($timeFactory);

		// Every 5 minutes
		$this->setInterval(60 * 5);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);

		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->db = $db;
		$this->rootFolder = $rootFolder;
		$this->mimeTypeLoader = $mimeTypeLoader;
		$this->config = $config;
		$this->client = $client;
		$this->verify = $verify;
	}

	protected function run($argument): void {
		if (!$this->config->isBackgroundVerifyEnabled()) {
			$this->logger->info('Background verification disabled');
			return;
		}

		$account = $this->config->getAccount();
		if (!$account['id'] || !$account['secret']) {
			$this->logger->info('No account configured');
			return;
		}

		$this->logger->debug('Starting background verification');

		try {
			$result = $this->getPendingFiles();
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return;
		}

		$batchSize = $this->getBatchSize();
		$server = $this->config->getApiServer();
		$cnt = 0;
		while (($row = $result->fetch()) && $cnt < $batchSize) {
			try {
				$fileId = $row['fileid'];
				$users = $this->getUserWithAccessToStorage((int)$row['storage']);

				foreach ($users as $user) {
					/** @var IUser $owner */
					$owner = $this->userManager->get($user['user_id']);
					if (!$owner instanceof IUser) {
						continue;
					}

					$userFolder = $this->rootFolder->getUserFolder($owner->getUID());
					$files = $userFolder->getById($fileId);
					if (empty($files)) {
						continue;
					}

					$file = array_pop($files);
					if (!$file instanceof File) {
						$this->logger->error('Tried to verify non file at ' . $file->getPath());
						break;
					}

					if (!$userFolder->nodeExists($userFolder->getRelativePath($file->getPath()))) {
						$this->logger->error('Tried to verify non-existing file at ' . $file->getPath());
						break;
					}

					$this->verifyFile($file, $account, $server);
					$cnt++;
					break;
				}
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}
		}

		$this->logger->debug('Background verification finished');
	}

	private function getBatchSize(): int {
		// TODO: Make this configurable?
		return 10;
	}

	protected function getUserWithAccessToStorage(int $storageId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('user_id')
			->from('mounts')
			->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)));

		$cursor = $qb->executeQuery();
		$data = $cursor->fetchAll();
		$cursor->closeCursor();
		return $data;
	}

	private function getPendingFiles(): IResult {
		$pdfMimeTypeId = $this->mimeTypeLoader->getId('application/pdf');

		$since = new \DateTime();
		$since->setTimezone(new \DateTimeZone('UTC'));
		$since = $since->sub(new \DateInterval('PT' . self::VERIFY_ERROR_DELAY_MINUTES . 'M'));

		$query = $this->db->getQueryBuilder();
		$query->select('fc.fileid', 'storage')
			->from('filecache', 'fc')
			->leftJoin('fc', 'c24_file_signatures', 'fs', $query->expr()->eq('fc.fileid', 'fs.file_id'))
			->leftJoin('fc', 'c24_verify_failed', 'vf', $query->expr()->eq('fc.fileid', 'vf.file_id'))
			->where($query->expr()->isNull('fs.file_id'))
			->andWhere($query->expr()->eq('mimetype', $query->expr()->literal($pdfMimeTypeId)))
			->andWhere($query->expr()->like('path', $query->expr()->literal('files/%')))
			->andWhere(
				$query->expr()->orX(
					$query->expr()->isNull('vf.updated'),
					$query->expr()->lte('vf.updated', $query->createNamedParameter($since, 'datetimetz'))
				),
			)
			->setMaxResults($this->getBatchSize() * 10);

		return $query->executeQuery();
	}

	private function verifyFile(File $file, array $account, string $server) {
		$this->logger->debug('Verifying file ' . $file->getPath());

		try {
			$signatures = $this->client->verifySignatures($file, $account, $server);
		} catch (ConnectException $e) {
			$this->logger->error('Error connecting to ' . $server . ' for ' . $file->getPath(), [
				'exception' => $e,
			]);
			return;
		} catch (\Exception $e) {
			$this->logger->error('Error sending request to ' . $server . ' for ' . $file->getPath(), [
				'exception' => $e,
			]);
			$this->verify->storeFailed($file);
			return;
		}

		$this->verify->storeFileSignatures($file, $signatures);
	}
}
