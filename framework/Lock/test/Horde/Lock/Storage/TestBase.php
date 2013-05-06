<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @package    Lock
 * @subpackage UnitTests
 */
abstract class Horde_Lock_Storage_TestBase extends Horde_Test_Case
{
    protected $_lock;

    protected function setUp()
    {
        $this->_lock = $this->_getBackend();
    }

    abstract protected function _getBackend();

    public function tearDown()
    {
        unset($this->_lock);
    }

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

    public function testResetLock()
    {
        $lock1 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal', 100, Horde_Lock::TYPE_EXCLUSIVE);
        $info1 = $this->_lock->getLockInfo($lock1);
        $expiry = $info1['lock_expiry_timestamp'];

        $lock2 = $this->_lock->setLock(
            'myuser', 'myapp', 'myprincipal2', Horde_Lock::PERMANENT,
            Horde_Lock::TYPE_EXCLUSIVE);
        $info3 = $this->_lock->getLockInfo($lock2);

        sleep(1);

        $this->_lock->resetLock($lock1, 100);
        $info2 = $this->_lock->getLockInfo($lock1);
        $this->assertLessThan($info2['lock_update_timestamp'],
                              $info2['lock_origin_timestamp']);
        $this->assertLessThan($info2['lock_expiry_timestamp'],
                              $info2['lock_origin_timestamp']);
        $this->assertEquals($info2['lock_expiry_timestamp'],
                            $info2['lock_update_timestamp'] + 100);

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
        $this->assertEmpty($this->_lock->getLockInfo($lock1));
    }
}
