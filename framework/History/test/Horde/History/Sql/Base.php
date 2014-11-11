<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    History
 * @subpackage UnitTests
 */
abstract class Horde_History_Sql_Base extends Horde_History_TestBase
{
    protected static $db;
    protected static $dir;
    protected static $migrator;
    protected static $reason;
    protected static $logger;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$logger = new Horde_Log_Logger(new Horde_Log_Handler_Cli());
        //self::$db->setLogger(self::$logger);
        self::$dir = __DIR__ . '/../../../../migration/Horde/History';
        if (!is_dir(self::$dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            self::$dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_History/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$history = new Horde_History_Sql('test_user', self::$db);
    }

    public static function tearDownAfterClass()
    {
        if (self::$db) {
            self::$db->disconnect();
        }
        self::$db = self::$migrator = null;
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
        }
        parent::setUp();
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//self::$logger,
            array('migrationsPath' => self::$dir,
                  'schemaTableName' => 'horde_history_schema_info'));
        self::$migrator->up();
    }

    public function tearDown()
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
    }
}
