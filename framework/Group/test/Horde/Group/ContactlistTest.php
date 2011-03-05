<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';
require_once dirname(__FILE__) . '/Stub/Api.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @copyright  2011 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Group_ContactlistTest extends Horde_Group_Test_Base
{
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
        $this->markTestIncomplete();
        $this->_search();
    }

    public function testListUsers()
    {
        $this->_listUsers();
    }

    public function testListGroups()
    {
        $this->_listGroups();
    }

    public static function setUpBeforeClass()
    {
        self::$group = new Horde_Group_Contactlists(array('api' => new Horde_Group_Stub_Api()));
        self::$groupids = array('localsql:79ad3f08f267d15056650ee642a90b82',
                                'localsql:f44d8744352d9d3b6a5a1a72831e4cf4',
                                'localsql:43959c113d25605fbce585a46ff495d6');
    }
}
