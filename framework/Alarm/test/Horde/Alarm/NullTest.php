<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */

class Horde_Alarm_NullTest extends PHPUnit_Framework_TestCase
{
    protected static $alarm;
    protected static $date;
    protected static $end;

    public function testFactory()
    {
        self::$alarm = new Horde_Alarm_Null();
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
     * @depends testFactory
     */
    public function testExists()
    {
        $this->assertFalse(self::$alarm->exists('personalalarm', 'john'));
    }

    /**
     * @depends testFactory
     * @expectedException Horde_Alarm_Exception
     */
    public function testGet()
    {
        $alarm = self::$alarm->get('personalalarm', 'john');
    }

    /**
     * @depends testFactory
     */
    public function testListAlarms()
    {
        self::$alarm->set(array('id' => 'publicalarm',
                                'start' => self::$date,
                                'end' => self::$end,
                                'methods' => array(),
                                'params' => array(),
                                'title' => 'This is a public alarm.'));
        self::$date->min--;
        $list = self::$alarm->listAlarms('john');
        $this->assertEquals(0, count($list));
    }

    /**
     * @depends testFactory
     */
    public function testDelete()
    {
        self::$alarm->delete('publicalarm', '');
        $list = self::$alarm->listAlarms('john');
        $this->assertEquals(0, count($list));
    }

    /**
     * @depends testFactory
     * @expectedException Horde_Alarm_Exception
     */
    public function testSnoozeException()
    {
        self::$alarm->snooze('personalalarm', 'jane', 30);
    }

    /**
     * @depends testFactory
     */
    public function testSnooze()
    {
        $this->assertFalse(self::$alarm->isSnoozed('personalalarm', 'john'));
    }

    /**
     * @depends testFactory
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
        $this->assertEquals(0, count($list));
    }

    /**
     * @depends testFactory
     */
    public function testCleanUp()
    {
        self::$alarm->delete('noend', 'john');
        self::$alarm->delete('personalalarm', 'john');
    }
}
