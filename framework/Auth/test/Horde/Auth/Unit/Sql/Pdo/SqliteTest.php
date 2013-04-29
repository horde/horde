<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Base.php';

/**
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 */

class Horde_Auth_Unit_Sql_Pdo_SqliteTest extends Horde_Auth_Unit_Sql_Base
{
    public static function setUpBeforeClass()
    {
        $factory_db = new Horde_Test_Factory_Db();

        try {
            self::$db = $factory_db->create();
            parent::setUpBeforeClass();
        } catch (Horde_Test_Exception $e) {
            self::$_reason = 'Sqlite not available.';
        }
    }

}
