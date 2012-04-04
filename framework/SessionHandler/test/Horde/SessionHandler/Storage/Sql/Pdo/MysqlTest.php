<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    SessionHandler
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_SessionHandler_Storage_Sql_Pdo_MysqlTest extends Horde_SessionHandler_Storage_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('mysql', PDO::getAvailableDrivers())) {
            self::$reason = 'No mysql extension or no mysql PDO driver';
            return;
        }
        $config = self::getConfig('SESSIONHANDLER_SQL_PDO_MYSQL_TEST_CONFIG',
                                  dirname(__FILE__) . '/../../..');
        if ($config && !empty($config['sessionhandler']['sql']['pdo_mysql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Mysql($config['sessionhandler']['sql']['pdo_mysql']);
            parent::setUpBeforeClass();
        }
    }
}
