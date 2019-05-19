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

namespace OCA\LogExport\Analyzer;

use OCA\LogExport\AppInfo\Application;
use OCA\LogExport\Entropy\Entropy;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\Files\NotFoundException;
use OCP\ILogger;

class EntropyAnalyzer
{
    /**
     * Size of the data blocks in bytes.
     *
     * @var int
     */
    const BLOCK_SIZE = 256;

    /** @var ILogger */
    protected $logger;

    /** @var IRootFolder */
    protected $rootFolder;

    /** @var Entropy */
    protected $entropy;

    /** @var string */
    protected $userId;

    /**
     * @param ILogger     $logger
     * @param IRootFolder $rootFolder
     * @param Entropy     $entropy
     * @param int         $userId
     */
    public function __construct(
        ILogger $logger,
        IRootFolder $rootFolder,
        Entropy $entropy,
        $userId
    ) {
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
        $this->entropy = $entropy;
        $this->userId = $userId;
    }

    /**
     * @param File     $node
     *
     * @return EntropyResult
     */
    public function analyze($node)
    {
        $entropy = $this->calculateEntropyOfFile($node);
        $standardDeviation = $this->calculateStandardDeviationOfEntropy($node, self::BLOCK_SIZE);
        return new EntropyResult($entropy, $standardDeviation);
    }

    /**
     * Calculates standard deviation of the entropy of multiple data blocks.
     *
     * @param array $entropyArray
     *
     * @return float
     */
    protected function calculateStandardDeviationOfEntropy($node, $blockSize)
    {
        $sum = 0.0;
        $standardDeviation = 0.0;
        $mean = 1;
        $step = 1;

        $handle = $node->fopen('r');
        if (!$handle) {
            $this->logger->debug('createEntropyArrayFromFile: Getting file handle failed.', array('app' => Application::APP_ID));

            return 0.0;
        }

        while (!feof($handle)) {
            $data = fread($handle, $blockSize);
            $step = $step + 1;
            if (strlen($data) === $blockSize) {
                $entropy = $this->entropy->calculateEntropy($data);
                $sum = $sum + pow($entropy, 2);
                $mean = $this->entropy->calculateMeanOfSeries($mean, $entropy, $step);
                $standardDeviation = $this->entropy->calculateStandardDeviationOfSeries($step, $sum, $mean);
            }
        }
        fclose($handle);

        return $standardDeviation;
    }

    /**
     * Calculates the entropy of a file.
     *
     * @param File $node
     *
     * @return float
     */
    protected function calculateEntropyOfFile($node)
    {
        $handle = $node->fopen('r');
        if (!$handle) {
            $this->logger->debug('calculateEntropyOfFile: Getting data failed.', array('app' => Application::APP_ID));

            return 0.0;
        }

        $entropy = 0.0;
        $total = 0;

        while (!feof($handle)) {
            $data = fread($handle, 1024);
            $total = $total + 1;
            if (strlen($data) === 1024) {
                $entropy = $entropy + $this->entropy->calculateEntropy($data);
            }
        }
        fclose($handle);

        $entropy = $entropy / $total;

        if ($entropy >= 0) {
            return $entropy;
        } else {
            return -$entropy;
        }
    }
}
