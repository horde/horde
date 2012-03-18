<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Locks.php';

/**
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 */

class Horde_Auth_Unit_Sql_Pdo_SqliteLockTest extends Horde_Auth_Unit_Sql_Locks
{

    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('sqlite', PDO::getAvailableDrivers())) {
            self::$reason = 'No sqlite extension or no sqlite PDO driver';
            return;
        }
        self::$db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:', 'charset' => 'utf-8'));
        parent::setUpBeforeClass();
    }


}
