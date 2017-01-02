<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
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
 * This class tests a MySQL backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_Sql_MysqlTest extends Horde_Cache_Sql_Base
{
    protected function _getCache($params = array())
    {
        if (!extension_loaded('mysql')) {
            $this->reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('CACHE_SQL_MYSQL_TEST_CONFIG',
                                  __DIR__ . '/..');
        if ($config && !empty($config['cache']['sql']['mysql'])) {
            $this->db = new Horde_Db_Adapter_Mysql($config['cache']['sql']['mysql']);
            return parent::_getCache($params);
        } else {
            $this->reason = 'No mysql configuration';
        }
    }
}
