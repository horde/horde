<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Vfs_Test_Sql_Base extends Horde_Vfs_Test_Base
{
    protected static $db;

    protected static $migrator;

    protected static $reason;

    public function testListEmpty()
    {
        $this->_listEmpty();
    }

    public function testCreateFolder()
    {
        $this->_createFolderStructure();
    }

    /**
     * @depends testCreateFolder
     */
    public function testWriteData()
    {
        $this->_writeData();
    }

    /**
     * @depends testCreateFolder
     */
    public function testWrite()
    {
        $this->_write();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testRead()
    {
        $this->_read();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testSize()
    {
        $this->_size();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testFolderSize()
    {
        $this->_folderSize();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testVfsSize()
    {
        $this->_vfsSize();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testCopy()
    {
        $this->_copy();
    }

    /**
     * @depends testCopy
     */
    public function testRename()
    {
        $this->_rename();
    }

    /**
     * @depends testRename
     */
    public function testMove()
    {
        $this->_move();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testListFolder()
    {
        $this->_listFolder();
    }

    public static function setUpBeforeClass()
    {
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream(STDOUT));
        //self::$db->setLogger($logger);
        $logger = new Horde_Log_Logger(
            new Horde_Log_Handler_Stream(
                STDOUT, null,
                new Horde_Log_Formatter_Simple('%message%' . PHP_EOL)));
        $dir = dirname(__FILE__) . '/../../../../migration/Horde/Vfs';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Vfs/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => $dir,
                  'schemaTableName' => 'horde_vfss_test_schema'));
        self::$migrator->up();

        self::$vfs = new Horde_Vfs_Sql(array('db' => self::$db));
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