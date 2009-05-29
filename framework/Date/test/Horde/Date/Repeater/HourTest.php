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
class Horde_Date_Repeater_HourTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testNextFuture()
    {
        $hours = new Horde_Date_Repeater_Hour();
        $hours->now = $this->now;

        $nextHour = $hours->next('future');
        $this->assertEquals('2006-08-16 15:00:00', (string)$nextHour->begin);
        $this->assertEquals('2006-08-16 16:00:00', (string)$nextHour->end);

        $nextNextHour = $hours->next('future');
        $this->assertEquals('2006-08-16 16:00:00', (string)$nextNextHour->begin);
        $this->assertEquals('2006-08-16 17:00:00', (string)$nextNextHour->end);
    }

    public function testNextPast()
    {
        $hours = new Horde_Date_Repeater_Hour();
        $hours->now = $this->now;

        $pastHour = $hours->next('past');
        $this->assertEquals('2006-08-16 13:00:00', (string)$pastHour->begin);
        $this->assertEquals('2006-08-16 14:00:00', (string)$pastHour->end);

        $pastPastHour = $hours->next('past');
        $this->assertEquals('2006-08-16 12:00:00', (string)$pastPastHour->begin);
        $this->assertEquals('2006-08-16 13:00:00', (string)$pastPastHour->end);
    }

    public function testThis()
    {
        $hours = new Horde_Date_Repeater_Hour();
        $hours->now = new Horde_Date('2006-08-16 14:30:00');

        $thisHour = $hours->this('future');
        $this->assertEquals('2006-08-16 14:31:00', (string)$thisHour->begin);
        $this->assertEquals('2006-08-16 15:00:00', (string)$thisHour->end);

        $thisHour = $hours->this('past');
        $this->assertEquals('2006-08-16 14:00:00', (string)$thisHour->begin);
        $this->assertEquals('2006-08-16 14:30:00', (string)$thisHour->end);
    }

    public function testOffset()
    {
        $span = new Horde_Date_Span($this->now, $this->now->add(1));
        $hours = new Horde_Date_Repeater_Hour();

        $offsetSpan = $hours->offset($span, 3, 'future');
        $this->assertEquals('2006-08-16 17:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2006-08-16 17:00:01', (string)$offsetSpan->end);

        $offsetSpan = $hours->offset($span, 24, 'past');
        $this->assertEquals('2006-08-15 14:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2006-08-15 14:00:01', (string)$offsetSpan->end);
    }

}
