<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */

require_once dirname(__FILE__) . '/../../../lib/Horde/Date.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Date/Utils.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Date/Span.php';


/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_DateTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    public function tearDown()
    {
        date_default_timezone_set($this->_oldTimezone);
    }

    public function testConstructor()
    {
        $date = new stdClass();
        $date->year = 2001;
        $date->month = 2;
        $date->mday = 3;
        $date->hour = 4;
        $date->min = 5;
        $date->sec = 6;

        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date($date));
        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date((array)$date));
        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date(array('year' => 2001, 'month' => 2, 'day' => 3, 'hour' => 4, 'minute' => 5, 'sec' => 6)));
        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date('20010203040506'));
        $this->assertEquals('2001-02-03 05:05:06', (string)new Horde_Date('20010203T040506Z'));
        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date('2001-02-03 04:05:06'));
        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date(981169506));
    }

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

    public function testTimestamp()
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        $date = new Horde_Date(array('mday' => 1, 'month' => 10, 'year' => 2004));
        $this->assertEquals('1096603200', $date->timestamp());
        $this->assertEquals('1096603200', mktime(0, 0, 0, $date->month, $date->mday, $date->year));

        $date = new Horde_Date(array('mday' => 1, 'month' => 5, 'year' => 1948));
        $this->assertEquals('-683841600', $date->timestamp());
        $this->assertEquals('-683841600', mktime(0, 0, 0, $date->month, $date->mday, $date->year));

        date_default_timezone_set($oldTimezone);
    }

    public function testStrftime()
    {
        setlocale(LC_TIME, 'en_US.UTF-8');

        $date = new Horde_Date('2001-02-03 16:05:06');
        $format = '%C%n%d%n%D%n%e%n%H%n%I%n%m%n%M%n%R%n%S%n%t%n%T%n%y%n%Y%n%%';
        $this->assertEquals(strftime($format, $date->timestamp()), $date->strftime($format));

        $format = '%b%n%B%n%p%n%r%n%x%n%X';
        $this->assertEquals(strftime($format, $date->timestamp()), $date->strftime($format));

        $date->year = 1899;
        $expected = array(
            '18',
            '03',
            '02/03/99',
            ' 3',
            '16',
            '04',
            '02',
            '05',
            '16:05',
            '06',
            "\t",
            '16:05:06',
            '99',
            '1899',
            '%',
        );
        $format = '%C%n%d%n%D%n%e%n%H%n%I%n%m%n%M%n%R%n%S%n%t%n%T%n%y%n%Y%n%%';
        $this->assertEquals($expected, explode("\n", $date->strftime($format)));
    }

    public function testStrftimeDe()
    {
        setlocale(LC_TIME, 'de_DE');

        $date = new Horde_Date('2001-02-03 16:05:06');

        $format = '%b%n%B%n%p%n%r%n%x%n%X';
        $this->assertEquals(strftime($format, $date->timestamp()), $date->strftime($format));
    }

    public function testSetTimezone()
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        $date = new Horde_Date('20010203040506');
        $this->assertEquals('2001-02-03 04:05:06', (string)$date);

        $date->setTimezone('Europe/Berlin');
        $this->assertEquals('2001-02-03 10:05:06', (string)$date);

        $date = new Horde_Date('20010203040506', 'UTC');
        $this->assertEquals('2001-02-03 04:05:06', (string)$date);

        $date->setTimezone('Europe/Berlin');
        $this->assertEquals('2001-02-03 05:05:06', (string)$date);

        date_default_timezone_set($oldTimezone);
    }

    public function testDateMath()
    {
        $d = new Horde_Date('2008-01-01 00:00:00');

        $this->assertEquals('2007-12-31 00:00:00', (string)$d->sub(array('day' => 1)));
        $this->assertEquals('2009-01-01 00:00:00', (string)$d->add(array('year' => 1)));
        $this->assertEquals('2008-01-01 04:00:00', (string)$d->add(14400));

        $span = new Horde_Date_Span('2006-01-01 00:00:00', '2006-08-16 00:00:00');
        $this->assertEquals('2006-04-24 11:30:00', (string)$span->begin->add($span->width() / 2));
    }

    public function testSetNthWeekday()
    {
        $date = new Horde_Date('2004-10-01');

        $date->setNthWeekday(Horde_Date::DATE_SATURDAY);
        $this->assertEquals(2, $date->mday);

        $date->setNthWeekday(Horde_Date::DATE_SATURDAY, 2);
        $this->assertEquals(9, $date->mday);

        $date = new Horde_Date('2007-04-01');
        $date->setNthWeekday(Horde_Date::DATE_THURSDAY);
        $this->assertEquals(5, $date->mday);
    }

}
