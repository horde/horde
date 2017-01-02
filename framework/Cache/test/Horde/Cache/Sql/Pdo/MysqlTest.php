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
 * This class test a PDO MySQL backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_Sql_Pdo_MysqlTest extends Horde_Cache_Sql_Base
{
    protected function _getCache($params = array())
    {
        if (!extension_loaded('pdo') ||
            !in_array('mysql', PDO::getAvailableDrivers())) {
            $this->reason = 'No pdo_mysql extension';
            return;
        }
        $config = self::getConfig('CACHE_SQL_PDO_MYSQL_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if ($config && !empty($config['cache']['sql']['pdo_mysql'])) {
            $this->db = new Horde_Db_Adapter_Pdo_Mysql($config['cache']['sql']['pdo_mysql']);
            return parent::_getCache($params);
        } else {
            $this->reason = 'No pdo_mysql configuration';
        }
    }
}
