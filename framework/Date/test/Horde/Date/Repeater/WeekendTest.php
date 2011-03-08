<?php
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class Horde_Date_Repeater_WeekendTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testNextFuture()
    {
        $weekend = new Horde_Date_Repeater_Weekend();
        $weekend->now = $this->now;

        $nextWeekend = $weekend->next('future');
        $this->assertEquals('2006-08-19 00:00:00', (string)$nextWeekend->begin);
        $this->assertEquals('2006-08-21 00:00:00', (string)$nextWeekend->end);
    }

    public function testNextPast()
    {
        $weekend = new Horde_Date_Repeater_Weekend();
        $weekend->now = $this->now;

        $lastWeekend = $weekend->next('past');
        $this->assertEquals('2006-08-12 00:00:00', (string)$lastWeekend->begin);
        $this->assertEquals('2006-08-14 00:00:00', (string)$lastWeekend->end);
    }

    public function testThisFuture()
    {
        $weekend = new Horde_Date_Repeater_Weekend();
        $weekend->now = $this->now;

        $thisWeekend = $weekend->this('future');
        $this->assertEquals('2006-08-19 00:00:00', (string)$thisWeekend->begin);
        $this->assertEquals('2006-08-21 00:00:00', (string)$thisWeekend->end);
    }

    public function testThisPast()
    {
        $weekend = new Horde_Date_Repeater_Weekend();
        $weekend->now = $this->now;

        $thisWeekend = $weekend->this('past');
        $this->assertEquals('2006-08-12 00:00:00', (string)$thisWeekend->begin);
        $this->assertEquals('2006-08-14 00:00:00', (string)$thisWeekend->end);
    }

    public function testThisNone()
    {
        $weekend = new Horde_Date_Repeater_Weekend();
        $weekend->now = $this->now;

        $thisWeekend = $weekend->this('none');
        $this->assertEquals('2006-08-19 00:00:00', (string)$thisWeekend->begin);
        $this->assertEquals('2006-08-21 00:00:00', (string)$thisWeekend->end);
    }

    public function testOffset()
    {
        $weekend = new Horde_Date_Repeater_Weekend();
        $span = new Horde_Date_Span($this->now, $this->now->add(1));

        $offsetSpan = $weekend->offset($span, 3, 'future');
        $this->assertEquals('2006-09-02 00:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2006-09-02 00:00:01', (string)$offsetSpan->end);

        $offsetSpan = $weekend->offset($span, 1, 'past');
        $this->assertEquals('2006-08-12 00:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2006-08-12 00:00:01', (string)$offsetSpan->end);

        $offsetSpan = $weekend->offset($span, 0, 'future');
        $this->assertEquals('2006-08-12 00:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2006-08-12 00:00:01', (string)$offsetSpan->end);
    }

}
