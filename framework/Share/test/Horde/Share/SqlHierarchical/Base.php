<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Share_Test_SqlHierarchical_Base extends Horde_Share_Test_Base
{
    protected static $db;

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
        $share = parent::addShare();
        $this->assertInstanceOf('Horde_Share_Object_Sql_Hierarchical', $share);
        return $share->getId();
    }

    /**
     * @depends testAddShare
     */
    public function testPermissions($myshareid)
    {
        $shareids = parent::permissions($myshareid);
        return array(self::$share->getShareById($shareids[0]),
                     self::$share->getShareById($shareids[1]),
                     self::$share->getShareById($shareids[2]));
    }

    /**
     * @depends testAddShare
     */
    public function testExists()
    {
        $this->markTestSkipped('Not supported by hierarchical driver.');
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
     * @expectedException Horde_Share_Exception
     * @expectedExceptionMessage Share names are not supported in this driver
     */
    public function testGetShare()
    {
        parent::getShare();
    }

    /**
     * @depends testPermissions
     */
    public function testGetShareById(array $shares)
    {
        parent::getShareById($shares);
    }

    /**
     * @depends testPermissions
     */
    public function testGetShares(array $shares)
    {
        $newshares = self::$share->getShares(array($shares[0]->getId(), $shares[1]->getId(), $shares[2]->getId()));
        $this->assertType('array', $newshares);
        $this->assertEquals(3, count($newshares));
        $this->assertInstanceOf('Horde_Share_Object_Sql_Hierarchical', $newshares[0]);
        $this->assertInstanceOf('Horde_Share_Object_Sql_Hierarchical', $newshares[1]);
        $this->assertInstanceOf('Horde_Share_Object_Sql_Hierarchical', $newshares[2]);
        $this->assertEquals($newshares[0], $shares[0]);
        $this->assertEquals($newshares[1], $shares[1]);
        $this->assertEquals($newshares[2], $shares[2]);

        // Reset cache.
        self::$share->resetCache();

        $newshares = self::$share->getShares(array($shares[0]->getId(), $shares[1]->getId(), $shares[2]->getId()));
        $this->assertType('array', $newshares);
        $this->assertEquals(3, count($newshares));
        $this->assertInstanceOf('Horde_Share_Object_Sql_Hierarchical', $newshares[$shares[0]->getId()]);
        $this->assertInstanceOf('Horde_Share_Object_Sql_Hierarchical', $newshares[$shares[1]->getId()]);
        $this->assertInstanceOf('Horde_Share_Object_Sql_Hierarchical', $newshares[$shares[2]->getId()]);
        $this->assertEquals($newshares[$shares[0]->getId()], $shares[0]);
        $this->assertEquals($newshares[$shares[1]->getId()], $shares[1]);
        $this->assertEquals($newshares[$shares[2]->getId()], $shares[2]);
    }

    /**
     * @depends testPermissions
     */
    public function testListAllShares()
    {
        $this->markTestSkipped('Not supported by hierarchical driver.');
    }

    /**
     * @depends testPermissions
     */
    public function testListShares(array $shareids)
    {
        parent::listShares(array($shareids[0]->getId(), $shareids[1]->getId(), $shareids[2]->getId()));
    }

    /**
     * @depends testPermissions
     */
    public function testListSystemShares()
    {
        $this->markTestSkipped('Not supported by hierarchical driver.');
    }

    /**
     * @depends testPermissions
     */
    public function testRemoveUserPermissions(array $shares)
    {
        return parent::removeUserPermissions(array($shares[0]->getId(), $shares[1]->getId(), $shares[2]->getId()));
    }

    /**
     * @depends testRemoveUserPermissions
     */
    public function testRemoveGroupPermissions(array $shareids)
    {
        parent::removeGroupPermissions($shareids);
    }

    /**
     * @depends testPermissions
     */
    public function testRemoveShare(array $share)
    {
        parent::removeShare($share);
    }

    public function testCallback()
    {
        parent::callback(new Horde_Share_Object_Sql_Hierarchical(array()));
    }

    public function testListOwners()
    {
        $this->markTestIncomplete();
    }

    public function testCountOwners()
    {
        $this->markTestIncomplete();
    }

    public function testCountChildren()
    {
        $this->markTestIncomplete();
    }

    public function testGetParent()
    {
        $this->markTestIncomplete();
    }

    public function testGetParents()
    {
        $this->markTestIncomplete();
    }

    public function testSetParent()
    {
        $this->markTestIncomplete();
    }

    public static function setUpBeforeClass()
    {
        $migration = new Horde_Db_Migration_Base(self::$db);

        $t = $migration->createTable('test_shares', array('primaryKey' => 'share_id'));
        //$t->column('share_id', 'integer', array('null' => false, 'autoincrement' => true));
        $t->column('share_owner', 'string', array('limit' => 255));
        $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
        $t->column('share_parents', 'string', array('limit' => 255));
        $t->column('perm_creator', 'integer', array('default' => 0, 'null' => false));
        $t->column('perm_default', 'integer', array('default' => 0, 'null' => false));
        $t->column('perm_guest', 'integer', array('default' => 0, 'null' => false));
        $t->column('attribute_name', 'string', array('limit' => 255));
        $t->column('attribute_desc', 'string', array('limit' => 255));
        $t->end();

        $migration->addIndex('test_shares', array('share_owner'));
        $migration->addIndex('test_shares', array('perm_creator'));
        $migration->addIndex('test_shares', array('perm_default'));
        $migration->addIndex('test_shares', array('perm_guest'));
        $migration->addIndex('test_shares', array('share_parents'));

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
        self::$share = new Horde_Share_Sql_Hierarchical('test', 'john', new Horde_Perms(), $group);
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
