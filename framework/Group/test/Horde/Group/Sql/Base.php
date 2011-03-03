<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @copyright  2011 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
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
        $this->_exists();
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
     * @depends testListUsers
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
    public function testRemove()
    {
        $this->_remove();
    }

    public static function setUpBeforeClass()
    {
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream(STDOUT));
        //self::$db->setLogger($logger);
        // FIXME: get migration directory if not running from Git checkout.
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => dirname(__FILE__) . '/../../../../migration',
                  'schemaTableName' => 'horde_group_test_schema'));
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