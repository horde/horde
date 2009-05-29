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
class Horde_Date_Repeater_DayNameTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testNextFuture()
    {
        $mondays = new Horde_Date_Repeater_DayName('monday');
        $mondays->now = $this->now;

        $span = $mondays->next('future');
        $this->assertEquals('2006-08-21', $span->begin->format('Y-m-d'));
        $this->assertEquals('2006-08-22', $span->end->format('Y-m-d'));

        $span = $mondays->next('future');
        $this->assertEquals('2006-08-28', $span->begin->format('Y-m-d'));
        $this->assertEquals('2006-08-29', $span->end->format('Y-m-d'));
    }

    public function testNextPast()
    {
        $mondays = new Horde_Date_Repeater_DayName('monday');
        $mondays->now = $this->now;

        $span = $mondays->next('past');
        $this->assertEquals('2006-08-14', $span->begin->format('Y-m-d'));
        $this->assertEquals('2006-08-15', $span->end->format('Y-m-d'));

        $span = $mondays->next('past');
        $this->assertEquals('2006-08-07', $span->begin->format('Y-m-d'));
        $this->assertEquals('2006-08-08', $span->end->format('Y-m-d'));
    }

}
