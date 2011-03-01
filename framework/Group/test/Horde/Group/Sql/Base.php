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
class Horde_Group_Test_Sql extends Horde_Group_Test_Base
{
    protected static $db;

    protected static $reason;

    public static function setUpBeforeClass()
    {
        // FIXME: get migration directory if not running from Git checkout.
        $migrator = new Horde_Db_Migration_Migrator(self::$db, null, array('migrationsPath' => dirname(__FILE__) . '/../../../../migration'));
        $migrator->up();

        self::$group = Horde_Group::factory('Mock');
    }

    public static function tearDownAfterClass()
    {
        if (self::$db) {
            $migration = new Horde_Db_Migration_Base(self::$db);
            $migration->dropTable('horde_groups');
            $migration->dropTable('horde_groups_members');
            self::$db = null;
        }
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
        }
    }
}