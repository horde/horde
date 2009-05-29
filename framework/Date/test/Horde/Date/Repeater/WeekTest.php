<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_Repeater_WeekTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testNextFuture()
    {
        $weeks = new Horde_Date_Repeater_Week();
        $weeks->now = $this->now;

        $nextWeek = $weeks->next('future');
        $this->assertEquals('2006-08-20 00:00:00', (string)$nextWeek->begin);
        $this->assertEquals('2006-08-27 00:00:00', (string)$nextWeek->end);

        $nextNextWeek = $weeks->next('future');
        $this->assertEquals('2006-08-27 00:00:00', (string)$nextNextWeek->begin);
        $this->assertEquals('2006-09-03 00:00:00', (string)$nextNextWeek->end);
    }

    public function testNextPast()
    {
        $weeks = new Horde_Date_Repeater_Week();
        $weeks->now = $this->now;

        $lastWeek = $weeks->next('past');
        $this->assertEquals('2006-08-06 00:00:00', (string)$lastWeek->begin);
        $this->assertEquals('2006-08-13 00:00:00', (string)$lastWeek->end);

        $lastLastWeek = $weeks->next('past');
        $this->assertEquals('2006-07-30 00:00:00', (string)$lastLastWeek->begin);
        $this->assertEquals('2006-08-06 00:00:00', (string)$lastLastWeek->end);
    }

    public function testThisFuture()
    {
        $weeks = new Horde_Date_Repeater_Week();
        $weeks->now = $this->now;

        $thisWeek = $weeks->this('future');
        $this->assertEquals('2006-08-16 15:00:00', (string)$thisWeek->begin);
        $this->assertEquals('2006-08-20 00:00:00', (string)$thisWeek->end);
    }

    public function testThisPast()
    {
        $weeks = new Horde_Date_Repeater_Week();
        $weeks->now = $this->now;

        $thisWeek = $weeks->this('past');
        $this->assertEquals('2006-08-13 00:00:00', (string)$thisWeek->begin);
        $this->assertEquals('2006-08-16 14:00:00', (string)$thisWeek->end);
    }

    public function testOffset()
    {
        $weeks = new Horde_Date_Repeater_Week();
        $span = new Horde_Date_Span($this->now, $this->now->add(1));

        $offsetSpan = $weeks->offset($span, 3, 'future');
        $this->assertEquals('2006-09-06 14:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2006-09-06 14:00:01', (string)$offsetSpan->end);
    }

}
