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
        $factory_db = new Horde_Test_Factory_Db();

        try {
            self::$db = $factory_db->create();
            parent::setUpBeforeClass();
        } catch (Horde_Test_Exception $e) {
            self::$reason = 'Sqlite not available.';
        }
    }

}
