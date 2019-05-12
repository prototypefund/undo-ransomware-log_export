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

namespace OCA\BehaviourAnalyzer\Mapper;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class FileOperationMapper extends Mapper
{
    /**
     * @param IDBConnection $db
     */
    public function __construct(
        IDBConnection $db
    ) {
        parent::__construct($db, 'behaviour_analyzer');
    }

    /**
     * Find one by id.
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException            if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     *
     * @param int $id
     *
     * @return Entity
     */
    public function find($id, $userId)
    {
        $sql = 'SELECT * FROM `*PREFIX*behaviour_analyzer` '.
            'WHERE `id` = ? AND `user_id` = ?';

        return $this->findEntity($sql, [$id, $userId]);
    }

    /**
     * Find all.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function findAll(array $params = [], $limit = null, $offset = null)
    {
        $sql = 'SELECT * FROM `*PREFIX*behaviour_analyzer` WHERE `user_id` = ?';

        return $this->findEntities($sql, $params, $limit, $offset);
    }

    /**
     * Delete entity by id.
     *
     * @param int $id
     */
    public function deleteById($id, $userId)
    {
        $sql = 'DELETE FROM `*PREFIX*behaviour_analyzer` WHERE `id` = ? AND `user_id` = ?';
        $stmt = $this->execute($sql, [$id, $userId]);
        $stmt->closeCursor();
    }
}
