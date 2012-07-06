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
class Horde_Share_Test_Sqlng_Base extends Horde_Share_Test_Base
{
    protected static $db;

    public function testGetApp()
    {
        $this->getApp('test');
    }

    public function testSetTable()
    {
        $this->assertEquals('test_sharesng', self::$share->getTable());
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
        $share = parent::addShare();
        $this->assertInstanceOf('Horde_Share_Object_Sqlng', $share);
    }

    /**
     * @depends testAddShare
     */
    public function testPermissions()
    {
        parent::permissions();
    }

    /**
     * @depends testAddShare
     */
    public function testExists()
    {
        parent::exists();
    }

    /**
     * @depends testPermissions
     */
    public function testCountShares()
    {
        parent::countShares();
    }

    /**
     * @depends testPermissions
     */
    public function testGetShare()
    {
        $share = parent::getShare();
        $this->assertInstanceOf('Horde_Share_Object_Sqlng', $share);
    }

    /**
     * @depends testGetShare
     */
    public function testGetShareById()
    {
        parent::getShareById();
    }

    /**
     * @depends testGetShare
     */
    public function testGetShares()
    {
        parent::getShares();
    }

    /**
     */
    public function testGetParent()
    {
        $share = self::$share->getShare('myshare');
        $child = self::$share->getShare('mychildshare');
        $this->assertEquals($share->getId(), $child->getParent()->getId());
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
        parent::listAllShares();
    }

    /**
     * @depends testPermissions
     */
    public function testListShares()
    {
        parent::listShares();
    }

    /**
     * @depends testPermissions
     */
    public function testListSystemShares()
    {
        parent::listSystemShares();
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
        return parent::removeUserPermissions();
    }

    /**
     * @depends testRemoveUserPermissions
     */
    public function testRemoveGroupPermissions()
    {
        parent::removeGroupPermissions();
    }

    /**
     * @depends testGetShare
     */
    public function testRemoveShare()
    {
        parent::removeShare();
    }

    /**
     * @depends testGetShare
     */
    public function testRenameShare()
    {
        parent::renameShare();
    }

    public function testCallback()
    {
        parent::callback(new Horde_Share_Object_Sqlng(array()));
    }

    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/../migration/sqlng.php';
        migrate_sqlng(self::$db);

        $group = new Horde_Group_Test();
        self::$share = new Horde_Share_Sqlng('test', 'john', new Horde_Perms_Sql(array('db' => self::$db)), $group);
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
