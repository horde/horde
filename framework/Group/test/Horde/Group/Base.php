<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Group_Test_Base extends Horde_Test_Case
{
    protected static $group;

    protected static $groupids = array();

    protected function _create()
    {
        $this->markTestIncomplete();

        self::$groupids[] = self::$group->create('My Group');
        $this->assertNotNull(self::$groupid);
        self::$groupids[] = self::$group->create('My Other Group');
        self::$groupids[] = self::$group->create('Not My Group');
    }

    protected function _exists()
    {
        $this->markTestIncomplete();

        $this->assertTrue(self::$group->exists(self::$groupid));
        $this->assertFalse(self::$group->exists('some_non_existing_id'));
    }

    protected function _getName()
    {
        $this->markTestIncomplete();

        $this->assertEquals('My Group',
                            self::$group->getName(self::$groupids[0]));
        $this->assertEquals('My Other Group',
                            self::$group->getName(self::$groupids[1]));
        $this->assertEquals('Not My Group',
                            self::$group->getName(self::$groupids[2]));
    }

    protected function _listAll()
    {
        $this->markTestIncomplete();

        $groups = self::$group->list();
        $this->assertEquals(3, count($groups));
        $this->assertEquals('My Group',       $groups[self::$groupids[0]]);
        $this->assertEquals('My Other Group', $groups[self::$groupids[1]]);
        $this->assertEquals('Not My Group',   $groups[self::$groupids[2]]);
    }

    protected function _search()
    {
        $this->markTestIncomplete();

        $groups = self::$group->search('My Group');
        $this->assertEquals(2, count($groups));
        $this->assertEquals('My Group',     $groups[self::$groupids[0]]);
        $this->assertEquals('Not My Group', $groups[self::$groupids[2]]);
    }

    protected function _addUser()
    {
        $this->markTestIncomplete();

        $this->assertNull(self::$group->addUser(self::$groupids[0], 'joe'));
        self::$group->addUser(self::$groupids[1], 'joe');
        self::$group->addUser(self::$groupids[1], 'jane');
    }

    protected function _listUsers()
    {
        $this->markTestIncomplete();

        $users = self::$group->listUsers(self::$groupids[0]);
        $this->assertEquals(1, count($users));
        $this->assertTrue(in_array('joe', $users));
        $users = self::$group->listUsers(self::$groupids[1]);
        $this->assertEquals(2, count($users));
        $this->assertTrue(in_array('joe', $users));
        $this->assertTrue(in_array('jane', $users));
    }

    protected function _listGroups()
    {
        $this->markTestIncomplete();

        $groups = self::$group->listGroups('joe');
        $this->assertEquals(2, count($groups));
        $this->assertEquals('My Group',       $groups[self::$groupids[0]]);
        $this->assertEquals('My Other Group', $groups[self::$groupids[1]]);
        $groups = self::$group->listGroups('jane');
        $this->assertEquals(1, count($groups));
        $this->assertEquals('My Other Group', $groups[self::$groupids[1]]);
    }

    protected function _removeUser()
    {
        $this->markTestIncomplete();

        $this->assertNull(self::$group->removeUser(self::$groupids[1], 'joe'));
        $groups = self::$group->listGroups('joe');
        $this->assertEquals(1, count($groups));
        $this->assertEquals('My Group',       $groups[self::$groupids[0]]);
        $this->assertNull(self::$group->removeUser(self::$groupids[1], 'jane'));
        $groups = self::$group->listGroups('jane');
        $this->assertEquals(0, count($groups));
    }

    protected function _rename()
    {
        $this->markTestIncomplete();

        self::$group->rename(self::$groupids[1], 'My Second Group');
        $this->assertEquals('My Second Group',
                            self::$group->getName(self::$groupids[1]));
    }

    protected function _remove()
    {
        $this->markTestIncomplete();

        self::$group->remove(self::$groupids[0]);
        $this->assertFalse(self::$group->exists(self::$groupids[0]));
    }
}