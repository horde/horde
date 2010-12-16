<?php
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

        return self::$db;
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
