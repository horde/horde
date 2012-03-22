<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 */

class Horde_Auth_Unit_Sql_Base extends Horde_Auth_TestCase
{
    protected static $db;

    protected static $auth;

    protected static $migrator;

    protected static $reason;

    public static function setUpBeforeClass()
    {
        $dir = __DIR__ . '/../../../../../migration/Horde/Auth';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Auth/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,
            array('migrationsPath' => $dir,
                  'schemaTableName' => 'horde_auth_test_schema'));
        self::$migrator->up();

        self::$auth = new Horde_Auth_Sql(array('db' => self::$db, 'encryption' => 'plain'));
        // Don't rely on auth->addUser as this is the unit under test
        $row = "INSERT INTO horde_users VALUES ('mozilla', 'liketokyo', NULL, NULL);";
        self::$db->execute($row);
        $row = "INSERT INTO horde_users VALUES ('konqui', 'kde', NULL, NULL);";
        self::$db->execute($row);
        $row = "INSERT INTO horde_users VALUES ('tux', 'fish', NULL, NULL);";
        self::$db->execute($row);
    }

    public static function tearDownAfterClass()
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
        self::$db = null;
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
        }
    }

     public function testAuthenticate()
     {
         $this->assertTrue(self::$auth->authenticate('tux', array('password' => 'fish')));
     }

    public function testListUsers()
    {
        $resultUnsorted = self::$auth->listUsers();
        sort($resultUnsorted);
        $this->assertEquals(array('konqui', 'mozilla', 'tux'), $resultUnsorted);
    }
    public function testListUsersWithSorting()
    {
        $this->assertEquals(array('konqui', 'mozilla', 'tux'), self::$auth->listUsers(true));
    }

    public function testLockCapability()
    {
        $this->assertFalse(self::$auth->hasCapability('lock'));
    }

    public function testExistsReturnsTrueForPresentUser()
    {
        $this->assertTrue(self::$auth->exists('konqui'));
    }

    public function testExistsReturnsFalseForMissingUser()
    {
        $this->assertFalse(self::$auth->exists('beasty'));
    }
}
