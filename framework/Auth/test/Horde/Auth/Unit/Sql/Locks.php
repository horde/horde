<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 */

class Horde_Auth_Unit_Sql_Locks extends Horde_Auth_Unit_Sql_Base
{
    protected static $locksMigrator;

    protected static $locks;

    protected static $skip = '';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        if (is_dir(__DIR__ .'/../../../../../../Lock/migration')) {
            $lockMigrationsPath = __DIR__ .'/../../../../../../Lock/migration';
        } elseif (is_dir(__DIR__ .'/../../../../../../deps/Lock/migration')) {
            $lockMigrationsPath = __DIR__ .'/../../../../../../deps/Lock/migration';
            // how would that work for any possible pear_dir ?
        } else {
            self::$skip = 'Could not determine path to Horde_Lock migration';
            return;
        }
        self::$locksMigrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => $lockMigrationsPath,
                  'schemaTableName' => 'horde_lock_test_schema'));
        self::$locksMigrator->up();

        self::$locks = new Horde_Lock_Sql(array('db' => self::$db));

        self::$auth = new Horde_Auth_Sql(array('db' => self::$db,
                                                'encryption' => 'plain',
                                                'lock_api'   => self::$locks
                                                ));

    }

    public function setUp()
    {
        if (!class_exists('Horde_Db')) {
            $this->markTestSkipped('The Horde_Db package is not installed!');
        }
        if (!class_exists('Horde_Lock')) {
            $this->markTestSkipped('The Horde_Lock package is not installed!');
        }
        if (self::$skip) {
            $this->markTestSkipped(self::$skip);
        }
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
        } else {
            // portability: use DELETE because SQLite has no truncate
            $sql = "DELETE FROM horde_locks";
            self::$db->execute($sql);
        }
    }


     public function testAuthenticate()
     {
         $this->assertTrue(self::$auth->authenticate('tux', array('password' => 'fish')));
     }


    public function testLockUserOnceWorks()
    {
        self::$auth->lockUser('konqui');
    }

    /**
     * @expectedException Horde_Auth_Exception
     */

    public function testLockUserTwiceFails()
    {
        self::$auth->lockUser('konqui');
        self::$auth->lockUser('konqui');
    }

    public function testLockCapability()
    {
        $this->assertTrue(self::$auth->hasCapability('lock'));
    }

    public function testLockedUserReportsAsLocked()
    {
        self::$auth->lockUser('konqui');
        $this->assertTrue(self::$auth->isLocked('konqui'));
    }

    public function testLockedUserCannotLogin()
    {
        self::$auth->lockUser('konqui');
        $this->assertFalse(self::$auth->authenticate('konqui', array('password' => 'kde')));
    }

    public function testUnlockUnlockedDoesNotThrowException()
    {
        self::$auth->unlockUser('konqui');
        self::$auth->unlockUser('konqui');
        self::$auth->unlockUser('konqui');
    }

}
