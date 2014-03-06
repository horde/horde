<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */

class Horde_Alarm_SqlTest extends Horde_Test_Case
{
    protected static $skip = false;
    protected static $db;
    protected static $migrator;
    protected static $alarm;
    protected static $date;
    protected static $end;

    public static function setUpBeforeClass()
    {
        // @fixme
        $GLOBALS['language'] = 'en_US';

        $conf = self::getConfig('ALARM_TEST_CONFIG');
        if (!isset($conf['alarm']['test'])) {
            self::$skip = true;
            return;
        }

        $adapter = str_replace(' ', '_' , ucwords(str_replace('_', ' ', basename($conf['alarm']['test']['horde']['adapter']))));
        $class = 'Horde_Db_Adapter_' . $adapter;
        self::$db = new $class($conf['alarm']['test']['horde']);

        self::$migrator = new Horde_Db_Migration_Migrator(self::$db, null, array('migrationsPath' => dirname(dirname(dirname(__DIR__))) . '/migration/Horde/Alarm'));
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
       // $this->assertEquals('03052014', $alarm['instanceid']);
        $hash['instanceid'] = '03062014';
        $hash['title'] = 'This is the second instance';
        self::$alarm->set($hash);
        $alarm = self::$alarm->get('123', 'john');
        $this->assertEquals('123', $alarm['id']);
        $this->assertEquals('This is the second instance', $alarm['title']);
        //$this->assertEquals('03062014', $alarm['insatnceid']);

        // clean
        self::$alarm->delete('123', 'john');
    }

    /**
     * @depends testExists
     */
    public function testGet()
    {
        $alarm = self::$alarm->get('personalalarm', 'john');
        $this->assertInternalType('array', $alarm);
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
