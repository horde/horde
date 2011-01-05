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
class Horde_Share_Test_Sql_Base extends Horde_Share_Test_Base
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
        $share = parent::baseAddShare();
        $this->assertInstanceOf('Horde_Share_Object_Sql', $share);
        return $share->getId();
    }

    /**
     * @depends testAddShare
     */
    public function testPermissions($myshareid)
    {
        return parent::basePermissions($myshareid);
    }

    /**
     * @depends testAddShare
     */
    public function testExists()
    {
        parent::baseExists();
    }

    /**
     * @depends testPermissions
     */
    public function testCountShares()
    {
        parent::baseCountShares();
    }

    /**
     * @depends testPermissions
     */
    public function testGetShare()
    {
        $shares = parent::baseGetShare();
        $this->assertInstanceOf('Horde_Share_Object_Sql', $shares[0]);
        $this->assertInstanceOf('Horde_Share_Object_Sql', $shares[1]);
        $this->assertInstanceOf('Horde_Share_Object_Sql', $shares[2]);
        return $shares;
    }

    /**
     * @depends testGetShare
     */
    public function testGetShareById(array $shares)
    {
        parent::baseGetShareById($shares);
    }

    /**
     * @depends testGetShare
     */
    public function testGetShares(array $shares)
    {
        parent::baseGetShares($shares);
    }

    /**
     * @depends testPermissions
     */
    public function testListAllShares()
    {
        parent::baseListAllShares();
    }

    /**
     * @depends testPermissions
     */
    public function testListShares(array $shareids)
    {
        parent::baseListShares($shareids);
    }

    /**
     * @depends testPermissions
     */
    public function testListSystemShares()
    {
        parent::baseListSystemShares();
    }

    /**
     * @depends testGetShare
     */
    public function testRemoveShare(array $share)
    {
        parent::baseRemoveShare($share);
    }

    public static function setUpBeforeClass()
    {
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
