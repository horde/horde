<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */
class Horde_Alarm_Storage_NullTest extends Horde_Alarm_Storage_Base
{
    public function testFactory()
    {
        self::$alarm = new Horde_Alarm_Null();
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
    public function testUpdate($alarm)
    {
    }

    /**
     * @depends testFactory
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
}
