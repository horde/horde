<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */
abstract class Horde_Alarm_Storage_Sql_Base extends Horde_Alarm_Storage_Base
{
    protected static $db;
    protected static $migrator;
    protected static $reason;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Cli());
        //self::$db->setLogger($logger);
        $dir = __DIR__ . '/../../../../../migration/Horde/Alarm';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Alarm/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => $dir,
                  'schemaTableName' => 'horde_alarm_schema_info'));
        self::$migrator->up();
    }

    public static function tearDownAfterClass()
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
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
    }

    public function testFactory()
    {
        self::$alarm = new Horde_Alarm_Sql(array('db' => self::$db, 'charset' => 'UTF-8'));
        self::$alarm->initialize();
        self::$alarm->gc(true);
    }

    /**
     * @depends testFactory
     */
    public function testSetWithInstanceId()
    {
        $now = time();
        $date = new Horde_Date($now);
        $end = new Horde_Date($now + 3600);
        $hash = array('id' => '123',
                      'user' => 'john',
                      'start' => $date,
                      'end' => $end,
                      'methods' => array(),
                      'params' => array(),
                      'title' => 'This is the first instance',
                      'instanceid' => '03052014');

        self::$alarm->set($hash);
        $alarm = self::$alarm->get('123', 'john');
        $this->assertEquals('123', $alarm['id']);
        $this->assertEquals('This is the first instance', $alarm['title']);
        $hash['instanceid'] = '03062014';
        $hash['title'] = 'This is the second instance';
        self::$alarm->set($hash);
        $alarm = self::$alarm->get('123', 'john');
        $this->assertEquals('123', $alarm['id']);
        $this->assertEquals('This is the second instance', $alarm['title']);

        // clean
        self::$alarm->delete('123', 'john');
    }
}
