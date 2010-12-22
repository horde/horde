<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Share_SqlTest extends PHPUnit_Framework_TestCase
{
    protected static $db;

    protected static $share;

    public function testGetApp()
    {
        $this->assertEquals('test', self::$share->getApp());
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
        $share = self::$share->newShare('john', 'myshare');
        $this->assertInstanceOf('Horde_Share_Object_Sql', $share);
        self::$share->addShare($share);
    }

    /**
     * @depends testAddShare
     */
    public function testPermissions()
    {
        // System share.
        $share = self::$share->newShare(null, 'systemshare');
        $perm = $share->getPermission();
        $this->assertInstanceOf('Horde_Perms_Permission', $perm);
        $perm->addDefaultPermission(Horde_Perms::SHOW | Horde_Perms::READ);
        $share->setPermission($perm);
        $share->save();
        $this->assertTrue($share->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($share->hasPermission('john', Horde_Perms::READ));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::EDIT));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::DELETE));

        // Foreign share with user permissions.
        $share = self::$share->newShare('jane', 'janeshare');
        $share->addUserPermission('john', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT);
        $share->save();
        $this->assertTrue($share->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($share->hasPermission('john', Horde_Perms::READ));
        $this->assertTrue($share->hasPermission('john', Horde_Perms::EDIT));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::DELETE));

        // Foreign share with group permissions.
        $share = self::$share->newShare('jane', 'groupshare');
        $share->addGroupPermission('mygroup', Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::DELETE);
        $share->save();
        $this->assertTrue($share->hasPermission('john', Horde_Perms::SHOW));
        $this->assertTrue($share->hasPermission('john', Horde_Perms::READ));
        $this->assertFalse($share->hasPermission('john', Horde_Perms::EDIT));
        $this->assertTrue($share->hasPermission('john', Horde_Perms::DELETE));

        // Foreign share without permissions.
        $share = self::$share->newShare('jane', 'noshare');
        $share->save();
    }

    /**
     * @depends testAddShare
     */
    public function testExists()
    {
        $this->assertTrue(self::$share->exists('myshare'));

        // Reset cache.
        self::$share->resetCache();

        $this->assertTrue(self::$share->exists('myshare'));
    }

    /**
     * @depends testPermissions
     */
    public function testCountShares()
    {
        // Getting shares from cache.
        $this->assertEquals(4, self::$share->countShares('john'));
        $this->assertEquals(2, self::$share->countShares('john', Horde_Perms::EDIT));

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $this->assertEquals(4, self::$share->countShares('john'));
        $this->assertEquals(2, self::$share->countShares('john', Horde_Perms::EDIT));
    }

    /**
     * @depends testAddShare
     */
    public function testGetShare()
    {
        $share = self::$share->getShare('myshare');
        $this->assertInstanceOf('Horde_Share_Object_Sql', $share);

        // Reset cache.
        self::$share->resetCache();

        $share = self::$share->getShare('myshare');
        $this->assertInstanceOf('Horde_Share_Object_Sql', $share);

        return array($share, self::$share->getShare('janeshare'), self::$share->getShare('groupshare'));
    }

    /**
     * @depends testGetShare
     */
    public function testGetShareById(array $shares)
    {
        $newshare = self::$share->getShareById($shares[0]->getId());
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshare);
        $this->assertEquals($shares[0], $newshare);
        $newshare = self::$share->getShareById($shares[1]->getId());
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshare);
        $this->assertEquals($shares[1], $newshare);
        $newshare = self::$share->getShareById($shares[2]->getId());
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshare);
        $this->assertEquals($shares[2], $newshare);

        // Reset cache.
        self::$share->resetCache();

        $newshare = self::$share->getShareById($shares[0]->getId());
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshare);
        $this->assertEquals($shares[0], $newshare);
        $newshare = self::$share->getShareById($shares[1]->getId());
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshare);
        $this->assertEquals($shares[1], $newshare);
        $newshare = self::$share->getShareById($shares[2]->getId());
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshare);
        $this->assertEquals($shares[2], $newshare);
    }

    /**
     * @depends testGetShare
     */
    public function testGetShares(array $shares)
    {
        $newshares = self::$share->getShares(array($shares[0]->getId(), $shares[1]->getId(), $shares[2]->getId()));
        $this->assertType('array', $newshares);
        $this->assertEquals(3, count($newshares));
        $this->assertArrayHasKey('myshare', $newshares);
        $this->assertArrayHasKey('janeshare', $newshares);
        $this->assertArrayHasKey('groupshare', $newshares);
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshares['myshare']);
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshares['janeshare']);
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshares['groupshare']);
        $this->assertEquals($newshares['myshare'], $shares[0]);
        $this->assertEquals($newshares['janeshare'], $shares[1]);
        $this->assertEquals($newshares['groupshare'], $shares[2]);

        // Reset cache.
        self::$share->resetCache();

        $newshares = self::$share->getShares(array($shares[0]->getId(), $shares[1]->getId(), $shares[2]->getId()));
        $this->assertType('array', $newshares);
        $this->assertEquals(3, count($newshares));
        $this->assertArrayHasKey('myshare', $newshares);
        $this->assertArrayHasKey('janeshare', $newshares);
        $this->assertArrayHasKey('groupshare', $newshares);
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshares['myshare']);
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshares['janeshare']);
        $this->assertInstanceOf('Horde_Share_Object_Sql', $newshares['groupshare']);
        $this->assertEquals($newshares['myshare'], $shares[0]);
        $this->assertEquals($newshares['janeshare'], $shares[1]);
        $this->assertEquals($newshares['groupshare'], $shares[2]);
    }

    /**
     * @depends testPermissions
     */
    public function testListAllShares()
    {
        // Getting shares from cache.
        $shares = self::$share->listAllShares();
        $this->assertType('array', $shares);
        $this->assertEquals(5, count($shares));
        $this->assertArrayHasKey('myshare', $shares);
        $this->assertArrayHasKey('systemshare', $shares);
        $this->assertArrayHasKey('janeshare', $shares);
        $this->assertArrayHasKey('groupshare', $shares);
        $this->assertArrayHasKey('noshare', $shares);

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $shares = self::$share->listAllShares();
        $this->assertType('array', $shares);
        $this->assertEquals(5, count($shares));
        $this->assertArrayHasKey('myshare', $shares);
        $this->assertArrayHasKey('systemshare', $shares);
        $this->assertArrayHasKey('janeshare', $shares);
        $this->assertArrayHasKey('groupshare', $shares);
        $this->assertArrayHasKey('noshare', $shares);
    }

    /**
     * @depends testPermissions
     */
    public function testListSystemShares()
    {
        // Getting shares from cache.
        $shares = self::$share->listSystemShares();
        $this->assertType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertArrayHasKey('systemshare', $shares);

        // Reset cache.
        self::$share->resetCache();

        // Getting shares from backend.
        $shares = self::$share->listSystemShares();
        $this->assertType('array', $shares);
        $this->assertEquals(1, count($shares));
        $this->assertArrayHasKey('systemshare', $shares);
    }

    /**
     * @depends testGetShare
     */
    public function testRemoveShare(array $share)
    {
        self::$share->removeShare($share[0]);
        $this->assertEquals(4, count(self::$share->listAllShares()));

        // Reset cache.
        self::$share->resetCache();

        $this->assertEquals(4, count(self::$share->listAllShares()));
    }

    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('sqlite', PDO::getAvailableDrivers())) {
            return;
        }

        self::$db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
        //self::$db->setLogger(new Horde_Log_Logger(new Horde_Log_Handler_Stream(STDOUT)));
        $migration = new Horde_Db_Migration_Base(self::$db);

        $t = $migration->createTable('test_shares', array('primaryKey' => 'share_id'));
        //$t->column('share_id', 'integer', array('null' => false, 'autoincrement' => true));
        $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
        $t->column('share_owner', 'string', array('limit' => 255));
        $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
        $t->column('perm_creator', 'integer', array('default' => 0, 'null' => false));
        $t->column('perm_default', 'integer', array('default' => 0, 'null' => false));
        $t->column('perm_guest', 'integer', array('default' => 0, 'null' => false));
        $t->column('attribute_name', 'string', array('limit' => 255));
        $t->column('attribute_desc', 'string', array('limit' => 255));
        $t->end();

        $migration->addIndex('test_shares', array('share_name'));
        $migration->addIndex('test_shares', array('share_owner'));
        $migration->addIndex('test_shares', array('perm_creator'));
        $migration->addIndex('test_shares', array('perm_default'));
        $migration->addIndex('test_shares', array('perm_guest'));

        $t = $migration->createTable('test_shares_groups');
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm', 'integer', array('null' => false));
        $t->end();

        $migration->addIndex('test_shares_groups', array('share_id'));
        $migration->addIndex('test_shares_groups', array('group_uid'));
        $migration->addIndex('test_shares_groups', array('perm'));

        $t = $migration->createTable('test_shares_users');
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('user_uid', 'string', array('limit' => 255));
        $t->column('perm', 'integer', array('null' => false));
        $t->end();

        $migration->addIndex('test_shares_users', array('share_id'));
        $migration->addIndex('test_shares_users', array('user_uid'));
        $migration->addIndex('test_shares_users', array('perm'));

        $migration->migrate('up');

        $group = new Horde_Group_Test();
        self::$share = new Horde_Share_Sql('test', 'john', new Horde_Perms(), $group);
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
        }
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped('No sqlite extension or no sqlite PDO driver.');
        }
    }
}

class Horde_Group_Test extends Horde_Group {
    public function __construct()
    {
    }

    public function __wakeup()
    {
    }

    public function userIsInGroup($user, $gid, $subgroups = true)
    {
        return $user == 'john' && $gid == 'mygroup';
    }

    public function getGroupMemberships($user, $parentGroups = false)
    {
        return $user == 'john' ? array('mygroup' => 'mygroup') : array();
    }
}
