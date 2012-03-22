<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Base.php';

class Content_Sql_Pdo_MysqlTest extends Content_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('mysql', PDO::getAvailableDrivers())) {
            self::$reason = 'No mysql extension or no mysql PDO driver';
            return;
        }
        $config = self::getConfig('GROUP_SQL_PDO_MYSQL_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if ($config && !empty($config['group']['sql']['pdo_mysql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Mysql($config['group']['sql']['pdo_mysql']);
            parent::setUpBeforeClass();
        }
    }
}
