<?php
/**
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * @author     Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Group_FileTest extends Horde_Group_TestBase
{
    /**
     * Group file to write to
     *
     * @var string
     */
    protected static $_groupfile = '';

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
    public function testListUsers()
    {
        $this->_listUsers();
    }

    /**
     * @depends testExists
     */
    public function testListGroups()
    {
        $this->_listGroups();
    }

    public function testGroupWithUmlaut()
    {
        $filename = Horde::getTempFile('Horde_Group_FileTest');

        $group_name = "Group with Umläut";
        $user_name = "joe";

        $fp = fopen($filename, "w");
        fprintf($fp, "$group_name:x:1:$user_name\n");
        fclose($fp);

        $params = array("filename" => $filename);
        $group = new Horde_Group_File($params);

        $this->assertTrue($group->exists($group_name));
        $this->assertEquals($group_name, $group->getName($group_name));
        $this->assertEquals(array($user_name), $group->listUsers($group_name));
    }

    public function testGidFromFile()
    {
        $params = array("filename" => self::$_groupfile, "use_gid" => true);
        self::$group = new Horde_Group_File($params);
        self::$groupids = array(1, 2, 3);

        $this->assertTrue(self::$group->exists(self::$groupids[0]));
        $this->assertTrue(self::$group->exists(self::$groupids[1]));
        $this->assertTrue(self::$group->exists(self::$groupids[2]));
        $this->assertFalse(self::$group->exists(4242424));

        $this->assertEquals("My Other Group", self::$group->getName(self::$groupids[1]));
    }

    public static function setUpBeforeClass()
    {
        self::$_groupfile = Horde::getTempFile('Horde_Group_FileTest');

        $fp = fopen(self::$_groupfile, "w");
        fprintf($fp, "My Group:x:1:joe\n");
        fprintf($fp, "My Other Group:x:2:joe,jane\n");
        fprintf($fp, "Not My Group:x:3:jeff,steve\n");
        fclose($fp);

        self::$groupids = array("My Group", "My Other Group", "Not My Group");
    }

    public function setUp()
    {
        $params = array("filename" => self::$_groupfile);
        self::$group = new Horde_Group_File($params);
        self::$groupids = array("My Group", "My Other Group", "Not My Group");
    }

    public static function tearDownAfterClass()
    {
        unlink(self::$_groupfile);
    }
}
