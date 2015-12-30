<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Group_MockTest extends Horde_Group_TestBase
{
    public function testListAllWithNoGroupsCreated()
    {
        $this->assertEquals(array(), self::$group->listAll());
    }

    public function testCreate()
    {
        $this->_create();
    }

    /**
     * @depends testCreate
     */
    public function testExists()
    {
        $this->_exists('some_none_existing_id');
    }

    /**
     * @depends testExists
     */
    public function testGetName()
    {
        $this->_getName();
    }

    /**
     * @depends testExists
     */
    public function testGetData()
    {
        $this->_getData();
    }

    /**
     * @depends testExists
     */
    public function testListAll()
    {
        $this->_listAll();
    }

    /**
     * @depends testExists
     */
    public function testSearch()
    {
        $this->_search();
    }

    /**
     * @depends testExists
     */
    public function testAddUser()
    {
        $this->_addUser();
    }

    /**
     * @depends testAddUser
     */
    public function testListUsers()
    {
        $this->_listUsers();
    }

    /**
     * @depends testAddUser
     */
    public function testListGroups()
    {
        $this->_listGroups();
    }

    /**
     * @depends testAddUser
     */
    public function testListAllWithMember()
    {
        $this->_listAllWithMember();
    }

    /**
     * @depends testListGroups
     */
    public function testRemoveUser()
    {
        $this->_removeUser();
    }

    /**
     * @depends testExists
     */
    public function testRename()
    {
        $this->_rename();
    }

    /**
     * @depends testExists
     */
    public function testSetData()
    {
        $this->_setData();
    }

    /**
     * @depends testExists
     */
    public function testRemove()
    {
        $this->_remove();
    }

    public function testCache()
    {
        if (!class_exists('Horde_Cache')) {
            $this->markTestSkipped('Horde_Cache not installed');
        }

        foreach (self::$groupids as $id) {
            self::$group->remove($id);
        }

        $id = self::$group->create('Cached Group');
        $this->assertTrue(self::$group->exists($id));
        self::$group->setCache(new Horde_Cache(new Horde_Cache_Storage_Memory()));

        $this->assertTrue(self::$group->exists($id));
        $log = self::$group->getLog();
        $this->assertEquals('_exists', array_pop($log));
        self::$group->clearLog();
        $this->assertTrue(self::$group->exists($id));
        $this->assertEquals(array(), self::$group->getLog());

        $this->assertEquals('Cached Group', self::$group->getName($id));
        $log = self::$group->getLog();
        $this->assertEquals('_getName', array_pop($log));
        self::$group->clearLog();
        $this->assertEquals('Cached Group', self::$group->getName($id));
        $this->assertEquals(array(), self::$group->getLog());
        self::$group->rename($id, 'Cached Group 2');
        self::$group->clearLog();
        $this->assertEquals('Cached Group 2', self::$group->getName($id));
        $this->assertEquals(array(), self::$group->getLog());

        $data = self::$group->getData($id);
        $this->assertEquals('Cached Group 2', $data['name']);
        $this->assertNull($data['email']);
        $log = self::$group->getLog();
        $this->assertEquals('_getData', array_pop($log));
        self::$group->clearLog();
        $data = self::$group->getData($id);
        $this->assertEquals('Cached Group 2', $data['name']);
        $this->assertNull($data['email']);
        $this->assertEquals(array(), self::$group->getLog());
        self::$group->setData($id, 'email', 'test@example.com');
        self::$group->clearLog();
        $data = self::$group->getData($id);
        $this->assertEquals('Cached Group 2', $data['name']);
        $this->assertEquals('test@example.com', $data['email']);
        $this->assertEquals(array(), self::$group->getLog());

        $this->assertEquals(
            array($id => 'Cached Group 2'),
            self::$group->listAll()
        );
        $log = self::$group->getLog();
        $this->assertEquals('_listAll', array_pop($log));
        self::$group->clearLog();
        $this->assertEquals(
            array($id => 'Cached Group 2'),
            self::$group->listAll()
        );
        $this->assertEquals(array(), self::$group->getLog());

        $this->assertEquals(
            array($id => 'Cached Group 2'),
            self::$group->search('Group')
        );
        $log = self::$group->getLog();
        $this->assertEquals('_search', array_pop($log));
        self::$group->clearLog();
        $this->assertEquals(
            array($id => 'Cached Group 2'),
            self::$group->search('Group')
        );
        $this->assertEquals(array(), self::$group->getLog());

        $this->assertEquals(array(), self::$group->listUsers($id));
        $log = self::$group->getLog();
        $this->assertEquals('_listUsers', array_pop($log));
        self::$group->clearLog();
        $this->assertEquals(array(), self::$group->listUsers($id));
        $this->assertEquals(array(), self::$group->getLog());

        self::$group->addUser($id, 'user1');
        self::$group->addUser($id, 'user2');
        self::$group->clearLog();

        $this->assertEquals(
            array('user1', 'user2'),
            self::$group->listUsers($id)
        );
        $this->assertEquals(array(), self::$group->getLog());
        self::$group->removeUser($id, 'user2');
        self::$group->clearLog();
        $this->assertEquals(array('user1'), self::$group->listUsers($id));
        $this->assertEquals(array(), self::$group->getLog());

        $this->assertEquals(
            array($id => 'Cached Group 2'),
            self::$group->listGroups('user1')
        );
        $log = self::$group->getLog();
        $this->assertEquals('_listGroups', array_pop($log));
        self::$group->clearLog();
        $this->assertEquals(
            array($id => 'Cached Group 2'),
            self::$group->listGroups('user1')
        );
        $this->assertEquals(array(), self::$group->getLog());
        $this->assertEquals(
            array($id => 'Cached Group 2'),
            self::$group->listAll('user1')
        );
        $this->assertEquals(array(), self::$group->getLog());

        self::$group->remove($id);
        self::$group->clearLog();
        $this->assertFalse(self::$group->exists($id));
        try {
            self::$group->getName($id);
            $this->markTestFailed('Should have thrown an exception');
        } catch (Horde_Exception_NotFound $e) {
        }
        try {
            self::$group->getData($id);
            $this->markTestFailed('Should have thrown an exception');
        } catch (Horde_Exception_NotFound $e) {
        }
        $this->assertEquals(array(), self::$group->getLog());
    }

    public static function setUpBeforeClass()
    {
        self::$group = new Horde_Group_Mock();
    }
}
