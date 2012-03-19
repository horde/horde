<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Base.php';

/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Group_Test_Sql_Base extends Horde_Group_Test_Base
{
    protected static $db;

    protected static $migrator;

    protected static $reason;

    public function testCreate()
    {
        $this->_create();
    }

    /**
     * @depends testCreate
     */
    public function testExists()
    {
        $this->_exists(99999);
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
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream(STDOUT));
        //self::$db->setLogger($logger);
        $logger = new Horde_Log_Logger(
            new Horde_Log_Handler_Stream(
                STDOUT, null,
                new Horde_Log_Formatter_Simple('%message%' . PHP_EOL)));
        $dir = __DIR__ . '/../../../../migration/Horde/Group';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Group/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => $dir,
                  'schemaTableName' => 'horde_groups_test_schema'));
        self::$migrator->up();

        self::$group = new Horde_Group_Sql(array('db' => self::$db));
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
}