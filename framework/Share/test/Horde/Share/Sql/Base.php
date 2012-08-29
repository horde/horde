<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Base.php';

/**
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Share_Test_Sql_Base extends Horde_Share_Test_Base
{
    protected static $db;

    public function testGetApp()
    {
        $this->getApp('test');
    }

    public function testSetTable()
    {
        $this->assertEquals('test_shares', self::$share->getTable());
        self::$share->setTable('foo');
        $this->assertEquals('foo', self::$share->getTable());
        self::$share->setTable('test_shares');
    }

    public function testSetStorage()
    {
        self::$share->setStorage(self::$db);
        $this->assertEquals(self::$db, self::$share->getStorage());
    }

    public function testAddShare()
    {
        $share = $this->addShare();
        $this->assertInstanceOf('Horde_Share_Object_Sql', $share);
    }

    /**
     * @depends testAddShare
     */
    public function testPermissions()
    {
        $this->permissions();
    }

    /**
     * @depends testAddShare
     */
    public function testExists()
    {
        $this->exists();
    }

    /**
     * @depends testPermissions
     */
    public function testCountShares()
    {
        $this->countShares();
    }

    /**
     * @depends testPermissions
     */
    public function testGetShare()
    {
        $share = $this->getShare();
        $this->assertInstanceOf('Horde_Share_Object_Sql', $share);
    }

    /**
     * @depends testAddShare
     */
    public function testHierarchy()
    {
        $this->hierarchy();
    }

    /**
     * @depends testGetShare
     */
    public function testGetShareById()
    {
        $this->getShareById();
    }

    /**
     * @depends testGetShare
     */
    public function testGetShares()
    {
        $this->getShares();
    }

    /**
     * @depends testPermissions
     */
     public function testListOwners()
     {
        $owners = self::$share->listOwners();
        $this->assertInternalType('array', $owners);
        $this->assertTrue(in_array('john', $owners));
     }

    /**
     * @depends testPermissions
     */
     public function testCountOwners()
     {
        $count = self::$share->countOwners();
        $this->assertTrue($count > 0);
     }

    /**
     * @depends testPermissions
     */
    public function testListAllShares()
    {
        $this->listAllShares();
    }

    /**
     * @depends testPermissions
     */
    public function testListShares()
    {
        $this->listShares();
    }

    /**
     * @depends testPermissions
     */
    public function testListSystemShares()
    {
        $this->listSystemShares();
    }

    /**
     * @depends testPermissions
     */
    public function testGetPermission()
    {
        return $this->getPermission();
    }

    /**
     * @depends testPermissions
     */
    public function testRemoveUserPermissions()
    {
        return $this->removeUserPermissions();
    }

    /**
     * @depends testRemoveUserPermissions
     */
    public function testRemoveGroupPermissions()
    {
        $this->removeGroupPermissions();
    }

    /**
     * @depends testGetShare
     */
    public function testRemoveShare()
    {
        $this->removeShare();
    }

    /**
     * @depends testGetShare
     */
    public function testRenameShare()
    {
        $this->renameShare();
    }

    public function testCallback()
    {
        $this->callback(new Horde_Share_Object_Sql(array()));
    }

    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/../migration/sql.php';
        migrate_sql(self::$db);

        $group = new Horde_Group_Test();
        self::$share = new Horde_Share_Sql('test', 'john', new Horde_Perms_Sql(array('db' => self::$db)), $group);
        self::$share->setStorage(self::$db);

        // FIXME
        $GLOBALS['injector'] = new Horde_Injector(new Horde_Injector_TopLevel());
        $GLOBALS['injector']->setInstance('Horde_Group', $group);
    }

    public static function tearDownAfterClass()
    {
        if (self::$db) {
            $migration = new Horde_Db_Migration_Base(self::$db);
            $migration->dropTable('test_shares');
            $migration->dropTable('test_shares_groups');
            $migration->dropTable('test_shares_users');
            self::$db = null;
        }
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped('No sqlite extension or no sqlite PDO driver.');
        }
    }
}
