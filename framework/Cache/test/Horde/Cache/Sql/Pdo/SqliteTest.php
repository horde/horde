<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */

/**
 * This class test a PDO SQLite backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_Sql_Pdo_SqliteTest extends Horde_Cache_Sql_Base
{
    protected function _getCache($params = array())
    {
        $factory_db = new Horde_Test_Factory_Db();
        try {
            $this->db = $factory_db->create();
        } catch (Horde_Test_Exception $e) {
            $this->$reason = 'Sqlite not available';
            return;
        }
        return parent::_getCache($params);
    }
}
