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
class Horde_Date_DateTest extends PHPUnit_Framework_TestCase
{
    public function testDateCorrection()
    {
        $d = new Horde_Date('2008-01-01 00:00:00');
        $d->month -= 2;
        $this->assertEquals(2007, $d->year);

        $d = new Horde_Date('2008-01-01 00:00:00');
        $d->day -= 1;
        $this->assertEquals(2007, $d->year);
        $this->assertEquals(12, $d->month);

        $d = new Horde_Date('2008-01-01 00:00:00');
        $d->day += 370;
        $this->assertEquals(2009, $d->year);
        $this->assertEquals(1, $d->month);

        $d = new Horde_Date('2008-01-01 00:00:00');
        $d->sec += 14400;
        $this->assertEquals(0, $d->sec);
        $this->assertEquals(0, $d->min);
        $this->assertEquals(4, $d->hour);
    }

    public function testDateMath()
    {
        $d = new Horde_Date('2008-01-01 00:00:00');

        $this->assertEquals('2007-12-31 00:00:00', (string)$d->sub(array('day' => 1)));
        $this->assertEquals('2009-01-01 00:00:00', (string)$d->add(array('year' => 1)));
        $this->assertEquals('2008-01-01 04:00:00', (string)$d->add(14400));
    }

}
