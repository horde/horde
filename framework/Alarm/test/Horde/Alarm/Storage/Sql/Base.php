<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */
class Horde_Alarm_Storage_Sql_Base extends Horde_Alarm_Storage_Base
{
    protected static $skip = false;
    protected static $db;
    protected static $migrator;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // @fixme
        $GLOBALS['language'] = 'en_US';

        $conf = self::getConfig('ALARM_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if (!isset($conf['alarm']['test'])) {
            self::$skip = true;
            return;
        }

        $migrationDir = __DIR__ . '/../../../../../migration/Horde/Alarm';
        if (!is_dir($migrationDir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $migrationDir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Alarm/migration';
            error_reporting(E_ALL | E_STRICT);
        }

        $adapter = str_replace(
            ' ',
            '_' ,
            ucwords(str_replace(
                '_',
                ' ',
                basename($conf['alarm']['test']['horde']['adapter'])
            ))
        );
        $class = 'Horde_Db_Adapter_' . $adapter;
        self::$db = new $class($conf['alarm']['test']['horde']);

        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,
            array(
                'migrationsPath' => $migrationDir,
                'schemaTableName' => 'horde_alarm_schema'
            )
        );
        self::$migrator->up();
    }

    public static function tearDownAfterClass()
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
    }

    public function setUp()
    {
        if (self::$skip) {
            $this->markTestSkipped('No configuration for Horde_Alarm test.');
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
