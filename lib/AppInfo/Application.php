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

namespace OCA\BehaviourAnalyzer\AppInfo;

use OC\Files\Filesystem;
use OCA\BehaviourAnalyzer\Monitor;
use OCA\BehaviourAnalyzer\Entropy\Entropy;
use OCA\BehaviourAnalyzer\StorageWrapper;
use OCA\BehaviourAnalyzer\Service\FileOperationService;
use OCA\BehaviourAnalyzer\Mapper\FileOperationMapper;
use OCP\AppFramework\App;
use OCP\Files\Storage\IStorage;
use OCP\Notification\IManager;
use OCP\Util;
use OCP\SabrePluginEvent;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\ISession;

class Application extends App
{
    const APP_ID = 'behaviour_analyzer';

    public function __construct()
    {
        parent::__construct(self::APP_ID);

        $container = $this->getContainer();

        // mapper
        $container->registerService('FileOperationMapper', function ($c) {
            return new FileOperationMapper(
                $c->query('ServerContainer')->getDb()
            );
        });

        // services
        $container->registerService('FileOperationService', function ($c) {
            return new FileOperationService(
                $c->query('FileOperationMapper'),
                $c->query('ServerContainer')->getUserSession()->getUser()->getUID()
            );
        });

        // entropy
        $container->registerService('Entropy', function ($c) {
            return new Entropy(
                $c->query(ILogger::class)
            );
        });
    }

    /**
     * Register hooks.
     */
    public function register()
    {
        Util::connectHook('OC_Filesystem', 'preSetup', $this, 'addStorageWrapper');
    }

    /**
     * @internal
     */
    public function addStorageWrapper()
    {
        Filesystem::addStorageWrapper(self::APP_ID, [$this, 'addStorageWrapperCallback'], -10);
    }

    /**
     * @internal
     *
     * @param string   $mountPoint
     * @param IStorage $storage
     *
     * @return StorageWrapper|IStorage
     */
    public function addStorageWrapperCallback($mountPoint, IStorage $storage)
    {
        if (!\OC::$CLI && !$storage->instanceOfStorage('OCA\Files_Sharing\SharedStorage')) {
            /** @var Monitor $monitor */
            $monitor = $this->getContainer()->query(Monitor::class);

            return new StorageWrapper([
                'storage' => $storage,
                'mountPoint' => $mountPoint,
                'monitor' => $monitor,
            ]);
        }

        return $storage;
    }
}
