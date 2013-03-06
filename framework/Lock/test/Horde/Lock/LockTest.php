<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @category   Horde
 * @package    Lock
 * @subpackage UnitTests
 */
class Horde_Lock_LockTest extends Horde_Test_Case
{
    /**
     * @var Horde_Lock_Sql
     */
    protected $_lock;

    protected static $_migrationDir;

    public static function setUpBeforeClass()
    {
        self::$_migrationDir = __DIR__ . '/../../../migration/Horde/Lock';
        if (!is_dir(self::$_migrationDir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            self::$_migrationDir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Group/migration';
            error_reporting(E_ALL | E_STRICT);
        }
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
        $migrator = new Horde_Db_Migration_Migrator(
            $db,
            null,
            array('migrationsPath' => self::$_migrationDir,
                  'schemaTableName' => 'horde_lock_test_schema'));
        $migrator->up();
        //$db->setLogger(new Horde_Log_Logger(new Horde_Log_Handler_Stream(STDOUT)));
        $this->_lock = new Horde_Lock_Sql(array('db' => $db));
    }

    /**
     * @covers Horde_Lock_Sql::getLockInfo
     */
    public function testGetLockInfo()
    {
        $lock1 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal', 100, Horde_Lock::TYPE_SHARED);
        $info1 = $this->_lock->getLockInfo($lock1);
        $this->assertEquals($info1['lock_update_timestamp'],
                            $info1['lock_origin_timestamp']);
        $this->assertEquals($info1['lock_expiry_timestamp'],
                            $info1['lock_origin_timestamp'] + 100);
        unset($info1['lock_update_timestamp'],
              $info1['lock_origin_timestamp'],
              $info1['lock_expiry_timestamp']);
        $this->assertEquals(array('lock_id' => $lock1,
                                  'lock_owner' => 'myuser',
                                  'lock_scope' => 'myapp',
                                  'lock_principal' => 'myprincipal',
                                  'lock_type' => Horde_Lock::TYPE_SHARED),
                            $info1);

        $lock2 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal2', 1000, Horde_Lock::TYPE_EXCLUSIVE);
        $info2 = $this->_lock->getLockInfo($lock2);
        $this->assertEquals($info2['lock_update_timestamp'],
                            $info2['lock_origin_timestamp']);
        $this->assertEquals($info2['lock_expiry_timestamp'],
                            $info2['lock_origin_timestamp'] + 1000);
        unset($info2['lock_update_timestamp'],
              $info2['lock_origin_timestamp'],
              $info2['lock_expiry_timestamp']);
        $this->assertEquals(array('lock_id' => $lock2,
                                  'lock_owner' => 'myuser',
                                  'lock_scope' => 'myapp',
                                  'lock_principal' => 'myprincipal2',
                                  'lock_type' => Horde_Lock::TYPE_EXCLUSIVE),
                            $info2);
    }

    /**
     * @covers Horde_Lock_Sql::getLocks
     */
    public function testGetLocks()
    {
        $lock1 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal', 100, Horde_Lock::TYPE_SHARED);
        $lock2 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal2', 100, Horde_Lock::TYPE_EXCLUSIVE);
        $this->assertEquals(
            array($lock1, $lock2),
            array_keys($this->_lock->getLocks('myapp')));
        $this->assertEquals(
            array($lock1),
            array_keys($this->_lock->getLocks('myapp', 'myprincipal')));
        $this->assertEquals(
            array($lock2),
            array_keys($this->_lock->getLocks(null, 'myprincipal2')));
        $this->assertEquals(
            array($lock1),
            array_keys($this->_lock->getLocks(null, null, Horde_Lock::TYPE_SHARED)));
        $this->assertEquals(
            array($lock2),
            array_keys($this->_lock->getLocks('myapp', 'myprincipal2', Horde_Lock::TYPE_EXCLUSIVE)));
        $this->assertEquals(
            array(),
            array_keys($this->_lock->getLocks('myapp', 'myprincipal', Horde_Lock::TYPE_EXCLUSIVE)));
    }

    /**
     * @covers Horde_Lock_Sql::resetLock
     */
    public function testResetLock()
    {
        $lock1 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal', 10, Horde_Lock::TYPE_EXCLUSIVE);
        $info1 = $this->_lock->getLockInfo($lock1);
        $expiry = $info1['lock_expiry_timestamp'];

        $lock2 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal2', Horde_Lock::PERMANENT,
            Horde_Lock::TYPE_EXCLUSIVE);
        $info3 = $this->_lock->getLockInfo($lock2);

        sleep(1);

        $this->_lock->resetLock($lock1, 10);
        $info2 = $this->_lock->getLockInfo($lock1);
        $this->assertLessThan($info2['lock_update_timestamp'],
                              $info2['lock_origin_timestamp']);
        $this->assertLessThan($info2['lock_expiry_timestamp'],
                              $info2['lock_origin_timestamp']);
        $this->assertEquals($info2['lock_expiry_timestamp'],
                            $info2['lock_update_timestamp'] + 10);

        $this->assertEquals(Horde_Lock::PERMANENT,
                            $info3['lock_expiry_timestamp']);
        $this->_lock->resetLock($lock2, Horde_Lock::PERMANENT);
        $info4 = $this->_lock->getLockInfo($lock2);
        $this->assertEquals(Horde_Lock::PERMANENT,
                            $info3['lock_expiry_timestamp']);

        $this->_lock->resetLock($lock1, Horde_Lock::PERMANENT);
        $info5 = $this->_lock->getLockInfo($lock1);
        $this->assertEquals(Horde_Lock::PERMANENT,
                            $info5['lock_expiry_timestamp']);
        $this->_lock->resetLock($lock2, 10);
        $info6 = $this->_lock->getLockInfo($lock2);
        $this->assertEquals(Horde_Lock::PERMANENT,
                            $info6['lock_expiry_timestamp']);
    }

    /**
     * @covers Horde_Lock_Sql::setLock
     */
    public function testSetLock()
    {
        $lock1 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal', 100, Horde_Lock::TYPE_SHARED);
        $lock2 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal', 100, Horde_Lock::TYPE_SHARED);
        $lock3 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal', 100, Horde_Lock::TYPE_EXCLUSIVE);
        $this->assertInternalType('string', $lock1);
        $this->assertInternalType('string', $lock2);
        $this->assertFalse($lock3);
        $this->assertNotEquals($lock1, $lock2);

        $lock4 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal2', 100, Horde_Lock::TYPE_EXCLUSIVE);
        $lock5 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal2', 100, Horde_Lock::TYPE_EXCLUSIVE);
        $lock6 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal2', 100, Horde_Lock::TYPE_SHARED);
        $this->assertInternalType('string', $lock4);
        $this->assertFalse($lock5);
        $this->assertFalse($lock6);

        $lock7 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal3', 1, Horde_Lock::TYPE_EXCLUSIVE);
        $lock8 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal4', Horde_Lock::PERMANENT,
            Horde_Lock::TYPE_EXCLUSIVE);
        sleep(2);
        $lock9 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal3', 100, Horde_Lock::TYPE_EXCLUSIVE);
        $lock10 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal4', Horde_Lock::PERMANENT,
            Horde_Lock::TYPE_EXCLUSIVE);
        $this->assertInternalType('string', $lock7);
        $this->assertInternalType('string', $lock8);
        $this->assertInternalType('string', $lock9);
        $this->assertFalse($lock10);
        $this->assertNotEquals($lock7, $lock9);
    }

    /**
     * @covers Horde_Lock_Sql::clearLock
     */
    public function testClearLock()
    {
        $lock1 = $this->_lock->setLock('myuser', 'myapp', 'myprincipal',
                                       100, Horde_Lock::TYPE_SHARED);
        $lock2 = $this->_lock->setLock('myuser', 'myapp', 'myprincipal2',
                                       100, Horde_Lock::TYPE_EXCLUSIVE);
        $this->_lock->clearLock($lock1);
        $this->assertEquals(
            array($lock2),
            array_keys($this->_lock->getLocks()));
        $this->assertFalse($this->_lock->getLockInfo($lock1));
    }
}
