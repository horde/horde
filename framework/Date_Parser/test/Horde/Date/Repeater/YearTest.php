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
class Horde_Date_Repeater_YearTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testNextFuture()
    {
        $years = new Horde_Date_Repeater_Year();
        $years->now = $this->now;

        $nextYear = $years->next('future');
        $this->assertEquals('2007-01-01', $nextYear->begin->format('Y-m-d'));
        $this->assertEquals('2008-01-01', $nextYear->end->format('Y-m-d'));

        $nextNextYear = $years->next('future');
        $this->assertEquals('2008-01-01', $nextNextYear->begin->format('Y-m-d'));
        $this->assertEquals('2009-01-01', $nextNextYear->end->format('Y-m-d'));
    }

    public function testNextPast()
    {
        $years = new Horde_Date_Repeater_Year();
        $years->now = $this->now;

        $lastYear = $years->next('past');
        $this->assertEquals('2005-01-01', $lastYear->begin->format('Y-m-d'));
        $this->assertEquals('2006-01-01', $lastYear->end->format('Y-m-d'));

        $lastLastYear = $years->next('past');
        $this->assertEquals('2004-01-01', $lastLastYear->begin->format('Y-m-d'));
        $this->assertEquals('2005-01-01', $lastLastYear->end->format('Y-m-d'));
    }

    public function testThis()
    {
        $years = new Horde_Date_Repeater_Year();
        $years->now = $this->now;

        $thisYear = $years->this('future');
        $this->assertEquals('2006-08-17', $thisYear->begin->format('Y-m-d'));
        $this->assertEquals('2007-01-01', $thisYear->end->format('Y-m-d'));

        $thisYear = $years->this('past');
        $this->assertEquals('2006-01-01', $thisYear->begin->format('Y-m-d'));
        $this->assertEquals('2006-08-16', $thisYear->end->format('Y-m-d'));
    }

    public function testOffset()
    {
        $span = new Horde_Date_Span($this->now, $this->now->add(1));
        $years = new Horde_Date_Repeater_Year();

        $offsetSpan = $years->offset($span, 3, 'future');
        $this->assertEquals('2009-08-16 14:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2009-08-16 14:00:01', (string)$offsetSpan->end);

        $offsetSpan = $years->offset($span, 10, 'past');
        $this->assertEquals('1996-08-16 14:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('1996-08-16 14:00:01', (string)$offsetSpan->end);
    }

}
