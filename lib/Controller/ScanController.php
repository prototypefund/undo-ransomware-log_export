<?php

/**
 * @copyright Copyright (c) 2019 Matthias Held <matthias.held@uni-konstanz.de>
 * @author Matthias Held <matthias.held@uni-konstanz.de>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace OCA\LogExport\Controller;

use OCA\LogExport\Monitor;
use OCA\LogExport\Analyzer\EntropyAnalyzer;
use OCA\LogExport\AppInfo\Application;
use OCA\LogExport\Db\FileOperation;
use OCA\LogExport\Exception\NotAFileException;
use OCA\LogExport\Service\FileOperationService;
use OCA\LogExport\Scanner\StorageStructure;
use OCP\Files\NotFoundException;
use OCA\Files_Trashbin\Trashbin;
use OCA\Files_Trashbin\Helper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\ILogger;

class ScanController extends Controller
{
    /** @var IConfig */
    protected $config;

    /** @var IUserSession */
    protected $userSession;

    /** @var ILogger */
    protected $logger;

    /** @var Folder */
    protected $userFolder;

    /** @var FileOperationService */
    protected $service;

    /** @var EntropyAnalyzer */
    protected $entropyAnalyzer;

    /** @var IDBConnection */
	protected $connection;

    /** @var string */
    protected $userId;

    /**
     * @param string               $appName
     * @param IRequest             $request
     * @param IUserSession         $userSession
     * @param IConfig              $config
     * @param ILogger              $logger
     * @param Folder               $userFolder
     * @param FileOperationService $service
     * @param EntropyAnalyzer      $entropyAnalyzer
     * @param IDBConnection        $connection
     * @param string               $userId
     */
    public function __construct(
        $appName,
        IRequest $request,
        IUserSession $userSession,
        IConfig $config,
        ILogger $logger,
        Folder $userFolder,
        FileOperationService $service,
        EntropyAnalyzer $entropyAnalyzer,
        IDBConnection $connection,
        $userId
    ) {
        parent::__construct($appName, $request);

        $this->config = $config;
        $this->userSession = $userSession;
        $this->userFolder = $userFolder;
        $this->logger = $logger;
        $this->service = $service;
        $this->entropyAnalyzer = $entropyAnalyzer;
        $this->connection = $connection;
        $this->userId = $userId;
    }

    /**
     * Scans a file.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse
     */
    public function scan($file)
    {
        return new JSONResponse(array('fileOperation' => $this->buildFileOperation($file)));
    }

    /**
     * The files to scan.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse
     */
    public function filesToScan()
    {
        $storageStructure = $this->getStorageStructure($this->userFolder);
        $trashStorageStructure = $this->getTrashStorageStructure();

        $fileOperations = array();

        // convert file to json and merge into one array
        $files = $storageStructure->getFiles();
        for ($i = 0; $i < count($files); $i++) {
            $file = ['id' => $files[$i]->getId(), 'path' => $files[$i]->getInternalPath(), 'timestamp' => $this->getLastActivity($files[$i]->getId())['timestamp']];
            $fileOperations[] = $file;
        }
        $trashFiles = $trashStorageStructure->getFiles();
        for ($i = 0; $i < count($trashFiles); $i++) {
            $fileOperation = ['id' => $trashFiles[$i]->getId(), 'path' => $trashFiles[$i]->getInternalPath(), 'timestamp' => $trashFiles[$i]->getMtime()];
            $fileOperations[] = $file;
        }

        // sort ASC for timestamp
        usort($fileOperations, function ($a, $b) {
            if ($a['timestamp'] === $b['timestamp']) {
                return 0;
            }
            return $b['timestamp'] - $a['timestamp'];
        });

        return new JSONResponse(array('fileOperations' => $fileOperations, 'numberOfFiles' => $storageStructure->getNumberOfFiles()));
    }

    /**
     * Download file activity.
     *
     * @NoAdminRequired
	 * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function download()
    {
        $storageStructure = $this->getStorageStructure($this->userFolder);
        $trashStorageStructure = $this->getTrashStorageStructure();

        $fileOperations = array();

        // convert file to json and merge into one array
        $files = $storageStructure->getFiles();
        for ($i = 0; $i < count($files); $i++) {
            $file = ['id' => $files[$i]->getId(), 'path' => $files[$i]->getInternalPath(), 'timestamp' => $this->getLastActivity($files[$i]->getId())['timestamp']];
            $fileOperations[] = $this->buildFileOperation($file);
        }
        $trashFiles = $trashStorageStructure->getFiles();
        for ($i = 0; $i < count($trashFiles); $i++) {
            $file = ['id' => $trashFiles[$i]->getId(), 'path' => $trashFiles[$i]->getInternalPath(), 'timestamp' => $trashFiles[$i]->getMtime()];
            $fileOperations[] = $this->buildFileOperation($file);
        }

        // sort ASC for timestamp
        usort($fileOperations, function ($a, $b) {
            if ($a->getTimestamp() === $b->getTimestamp()) {
                return 0;
            }
            return $b->getTimestamp() - $a->getTimestamp();
        });

        $response = new DataResponse($fileOperations);
		$response->addHeader('Content-Type', 'application/octet-stream');
		$response->addHeader('Content-Disposition', 'attachment; filename="log.json"');
		return $response;
    }

    /**
     * Just for testing purpose to mock the external static method.
     *
     * @return array
     */
    protected function getTrashFiles() {
        return Helper::getTrashFiles("/", $this->userId, 'mtime', false);
    }

    /**
     * Get last activity.
     * Visibility 'protected' is that it's possible to mock the database access.
     *
     * @param $objectId
     */
    protected function getLastActivity($objectId)
    {
        $query = $this->connection->getQueryBuilder();
    	$query->select('*')->from('activity');
        $query->where($query->expr()->eq('affecteduser', $query->createNamedParameter($this->userId)))
            ->andWhere($query->expr()->eq('object_id', $query->createNamedParameter($objectId)));
        $result = $query->execute();
        while ($row = $result->fetch()) {
            $rows[] = $row;
        }
        $result->closeCursor();
        if (isset($rows) && is_array($rows)) {
            return array_pop($rows);
        } else {
            $this->logger->debug('getLastActivity: No activity found.', array('app' => Application::APP_ID));
            return 0;
        }
    }

    /**
     * Builds a file operations from a file info array.
     *
     * @param  array $file
     * @return FileOperation
     */
    private function buildFileOperation($file)
    {
        $fileOperation = new FileOperation();
        $fileOperation->setUserId($this->userId);
        if (strpos($file['path'], 'files_trashbin') !== false) {
            $node = $this->userFolder->getParent()->get($file['path'] . '.d' . $file['timestamp']);
            $fileOperation->setCommand(Monitor::DELETE);
            $fileOperation->setTimestamp($file['timestamp']);
        } else {
            $node = $this->userFolder->getParent()->get($file['path']);
            $lastActivity = $this->getLastActivity($file['id']);
            $fileOperation->setCommand(Monitor::WRITE);
            $fileOperation->setTimestamp($lastActivity['timestamp']);
        }
        if (!($node instanceof File)) {
            throw new NotAFileException();
        }
        $fileOperation->setExtension(pathinfo($file['path'])['extension']);
        $fileOperation->setType('file');
        $fileOperation->setMimeType($node->getMimeType());
        $fileOperation->setSize($node->getSize());
        $fileOperation->setTimestamp($file['timestamp']);

        // entropy analysis
        $entropyResult = $this->entropyAnalyzer->analyze($node);
        $fileOperation->setEntropy($entropyResult->getEntropy());
        $fileOperation->setStandardDeviation($entropyResult->getStandardDeviation());

        return $fileOperation;
    }

    /**
     * Get trash storage structure.
     *
     * @return StorageStructure
     */
    private function getTrashStorageStructure()
    {
        $storageStructure = new StorageStructure(0, []);
        $nodes = $this->getTrashFiles();
        foreach ($nodes as $node) {
            $storageStructure->addFile($node);
            $storageStructure->increaseNumberOfFiles();
        }
        return $storageStructure;
    }

    /**
     * Get storage structure recursively.
     *
     * @param INode $node
     *
     * @return StorageStructure
     */
    private function getStorageStructure($node)
    {
        // set count for node to 0
        $storageStructure = new StorageStructure(0, []);
        if ($node instanceof Folder) {
            // it's a folder
            $nodes = $node->getDirectoryListing();
            if (count($nodes) === 0) {
                // folder is empty so nothing to do
                return $storageStructure;
            }
            foreach ($nodes as $tmpNode) {
                // analyse files in subfolder
                $tmpStorageStructure = $this->getStorageStructure($tmpNode);
                $storageStructure->setFiles(array_merge($storageStructure->getFiles(), $tmpStorageStructure->getFiles()));
                $storageStructure->setNumberOfFiles($storageStructure->getNumberOfFiles() + $tmpStorageStructure->getNumberOfFiles());
            }
            return $storageStructure;
        }
        else if ($node instanceof File) {
            // it's a file
            $storageStructure->addFile($node);
            $storageStructure->increaseNumberOfFiles();
            return $storageStructure;
        }
        else {
            // it's me Mario.
            // there is nothing else than file or folder
            $this->logger->error('getStorageStructure: Neither file nor folder.', array('app' => Application::APP_ID));
        }
    }
}
