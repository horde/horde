<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Base.php';

class Content_Sql_Pdo_PgsqlTest extends Content_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('pgsql', PDO::getAvailableDrivers())) {
            self::$reason = 'No pgsql extension or no pgsql PDO driver';
            return;
        }
        $config = self::getConfig('GROUP_SQL_PDO_PGSQL_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if ($config && !empty($config['group']['sql']['pdo_pgsql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Pgsql($config['group']['sql']['pdo_pgsql']);
            parent::setUpBeforeClass();
        }
    }
}
