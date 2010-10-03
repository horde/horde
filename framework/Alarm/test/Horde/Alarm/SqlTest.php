<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Horde_Alarm
 * @subpackage UnitTests
 */

class Horde_Alarm_SqlTest extends PHPUnit_Framework_TestCase
{
    protected static $pearConf;
    protected static $db;
    protected static $migrator;
    protected static $alarm;
    protected static $date;
    protected static $end;

    public static function setUpBeforeClass()
    {
        // @todo: remove when we no longer depend on DB.
        error_reporting(E_ALL);

        // @fixme
        $GLOBALS['language'] = 'en_US';

        $config = getenv('ALARM_TEST_CONFIG');
        if ($config === false) {
            $config = dirname(__FILE__) . '/conf.php';
        }
        if (file_exists($config)) {
            require $config;
        }
        if (!isset($conf['alarm']['test'])) {
            self::markTestSkipped('No configuration for Horde_Alarm test.');
            return;
        }

        self::$pearConf = $conf['alarm']['test']['pear'];

        $adapter = str_replace(' ', '_' , ucwords(str_replace('_', ' ', basename($conf['alarm']['test']['horde']['adapter']))));
        $class = 'Horde_Db_Adapter_' . $adapter;
        self::$db = new $class($conf['alarm']['test']['horde']);

        self::$migrator = new Horde_Db_Migration_Migrator(self::$db, null, array('migrationsPath' => dirname(dirname(dirname(dirname(__FILE__)))) . '/migrations'));
        self::$migrator->up();
    }

    public static function tearDownAfterClass()
    {
        self::$migrator->down();
    }

    public function testFactory()
    {
        self::$alarm = new Horde_Alarm_Sql(self::$pearConf);
        self::$alarm->initilize();
        self::$alarm->gc();
    }

    /**
     * @depends testFactory
     */
    public function testSet()
    {
        $now = time();
        self::$date = new Horde_Date($now);
        self::$end = new Horde_Date($now + 3600);
        $hash = array('id' => 'personalalarm',
                      'user' => 'john',
                      'start' => self::$date,
                      'end' => self::$end,
                      'methods' => array(),
                      'params' => array(),
                      'title' => 'This is a personal alarm.');
        self::$alarm->set($hash);
    }

    /**
     * @depends testSet
     */
    public function testExists()
    {
        $this->assertTrue(self::$alarm->exists('personalalarm', 'john'));
    }

    /**
     * @depends testExists
     */
    public function testGet()
    {
        $alarm = self::$alarm->get('personalalarm', 'john');
        $this->assertType('array', $alarm);
        $this->assertEquals('personalalarm', $alarm['id']);
        $this->assertEquals('john', $alarm['user']);
        $this->assertEquals(array(), $alarm['methods']);
        $this->assertEquals(array(), $alarm['params']);
        $this->assertEquals('This is a personal alarm.', $alarm['title']);
        $this->assertNull($alarm['text']);
        $this->assertNull($alarm['snooze']);
        $this->assertNull($alarm['internal']);
        $this->assertThat($alarm['start'], $this->isInstanceOf('Horde_Date'));
        $this->assertThat($alarm['end'], $this->isInstanceOf('Horde_Date'));
        $this->assertEquals(0, $alarm['start']->compareDateTime(self::$date));
        return $alarm;
    }

    /**
     * @depends testGet
     */
    public function testUpdate($alarm)
    {
        $alarm['title'] = 'Changed alarm text';
        self::$alarm->set($alarm);
    }

    /**
     * @depends testUpdate
     */
    public function testListAlarms()
    {
        self::$date->min--;
        self::$alarm->set(array('id' => 'publicalarm',
                                'start' => self::$date,
                                'end' => self::$end,
                                'methods' => array(),
                                'params' => array(),
                                'title' => 'This is a public alarm.'));
        $list = self::$alarm->listAlarms('john');
        $this->assertEquals(2, count($list));
        $this->assertEquals('publicalarm', $list[0]['id']);
        $this->assertEquals('personalalarm', $list[1]['id']);
    }

    /**
     * @depends testListAlarms
     */
    public function testDelete()
    {
        self::$alarm->delete('publicalarm', '');
        $list = self::$alarm->listAlarms('john');
        $this->assertEquals(1, count($list));
        $this->assertEquals('personalalarm', $list[0]['id']);
    }

    /**
     * @depends testDelete
     * @expectedException Horde_Alarm_Exception
     */
    public function testSnoozeException()
    {
        self::$alarm->snooze('personalalarm', 'jane', 30);
    }

    /**
     * @depends testDelete
     */
    public function testSnooze()
    {
        self::$alarm->snooze('personalalarm', 'john', 30);
        $this->assertTrue(self::$alarm->isSnoozed('personalalarm', 'john'));
        $list = self::$alarm->listAlarms('john');
        $this->assertEquals(0, count($list));
        $list = self::$alarm->listAlarms('john', self::$end);
        $this->assertEquals(1, count($list));
        $this->assertEquals('personalalarm', $list[0]['id']);

        /* Test resetting snooze after changing the alarm. */
        $alarm = self::$alarm->get('personalalarm', 'john');
        self::$alarm->set($alarm, true);
        $this->assertTrue(self::$alarm->isSnoozed('personalalarm', 'john'));
        self::$alarm->set($alarm);
        $this->assertFalse(self::$alarm->isSnoozed('personalalarm', 'john'));
    }

    /**
     * @depends testSnooze
     */
    public function testAlarmWithoutEnd()
    {
        self::$alarm->set(array('id' => 'noend',
                                'user' => 'john',
                                'start' => self::$date,
                                'methods' => array('notify'),
                                'params' => array(),
                                'title' => 'This is an alarm without end.'));
        $list = self::$alarm->listAlarms('john', self::$end);
        $this->assertEquals(2, count($list));
        $this->assertEquals('noend', $list[0]['id']);
        $this->assertEquals('personalalarm', $list[1]['id']);
    }

    /**
     * @depends testAlarmWithoutEnd
     */
    public function testCleanUp()
    {
        self::$alarm->delete('noend', 'john');
        self::$alarm->delete('personalalarm', 'john');
    }
}
