<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Group_MockTest extends Horde_Group_Test_Base
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

    public static function setUpBeforeClass()
    {
        self::$group = new Horde_Group_Mock();
    }
}
