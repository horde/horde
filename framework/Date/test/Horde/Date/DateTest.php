<?php
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

require_once __DIR__ . '/Autoload.php';

/**
 * @category   Horde
 * @package    Date
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
        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date('20010203T040506Z'));
        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date('2001-02-03 04:05:06'));
        $this->assertEquals('2001-02-03 04:05:06', (string)new Horde_Date(981169506));
        $date = new Horde_Date('2011-11-08 14:54:00 +0000');
        $date->setTimezone('UTC');
        $this->assertEquals('2011-11-08 14:54:00', (string)$date);

        $date = new Horde_Date('20010203T040506Z');
        $this->assertEquals('UTC', $date->timezone);
        $date->setTimezone('America/New_York');
        $newDate = new Horde_Date($date);
        $this->assertEquals('America/New_York', $newDate->timezone);
        $newDate->setTimezone('UTC');
        $this->assertEquals('2001-02-03 04:05:06', (string)$newDate);

        /* Test creating Horde_Date from DateTime with timezone explicitly set */
        $dt = new DateTime('2011-12-10T04:05:06', new DateTimeZone('Europe/Berlin'));
        $dt->setTimezone(new DateTimeZone('UTC'));
        $date = new Horde_Date($dt);
        $this->assertEquals('2011-12-10 03:05:06', (string)$date);
    }

    /**
     * Test creating a Horde_Date object representing the transition time
     * from DST to Standard Time
     *
     */
    public function testTZChangeDuringTransition()
    {
        // Standardize tz
        $oldtz = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        // This is a transition for America/New_York from EDST -> EST as
        // returned from DateTimeZone::getTransitions()
        // This one fails
        // $date = new Horde_Date('2011-11-06T06:00:00+0000');
        // $date->setTimezone('UTC');
        // $this->assertEquals('2011-11-06 06:00:00', (string)$date);

        // Even adjusting the minutes so the time is after the transition
        // doesn't help
        // $date = new Horde_Date('2011-11-06T06:10:00+0000');
        // $date->setTimezone('UTC');
        // $this->assertEquals('2011-11-06 06:10:00', (string)$date);

        // Once we pass the actual hour, it works
        $date = new Horde_Date('2011-11-06T07:00:00+0000');
        $date->setTimezone('UTC');
        $this->assertEquals('2011-11-06 07:00:00', (string)$date);

        // This one works
        $date = new Horde_Date('2011-03-13T07:00:00+0000');
        $date->setTimezone('UTC');
        $this->assertEquals('2011-03-13 07:00:00', (string)$date);

        date_default_timezone_set($oldtz);
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

        $d = new Horde_Date('2011-03-31 00:00:00');
        $d->month += 1;
        $this->assertEquals(5, $d->month);
        $this->assertEquals(1, $d->day);

        $d = new Horde_Date('2011-03-31 00:00:00');
        $d->month -= 1;
        $this->assertEquals(2, $d->month);
        $this->assertEquals(28, $d->day);

        $d = new Horde_Date('2011-02-28 00:00:00');
        $d->day += 1;
        $this->assertEquals(3, $d->month);
        $this->assertEquals(1, $d->day);

        $d = new Horde_Date('2011-03-01 00:00:00');
        $d->day -= 1;
        $this->assertEquals(2, $d->month);
        $this->assertEquals(28, $d->day);
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
        if (strpos(PHP_OS, 'WIN') === false) {
            $format = '%C%n%d%n%D%n%e%n%H%n%I%n%m%n%M%n%R%n%S%n%t%n%T%n%y%n%Y%n%%';
        } else {
            $format = "%d\n%H\n%I\n%m\n%M\n%S\n%y\n%Y\n%%";
        }
        $this->assertEquals(strftime($format, $date->timestamp()), $date->strftime($format));

        if (strpos(PHP_OS, 'WIN') === false) {
            $format = '%b%n%B%n%p%n%r%n%x%n%X';
        } else {
            $format = "%b\n%B\n%p\n%x\n%X";
        }
        $this->assertEquals(strftime($format, $date->timestamp()), $date->strftime($format));

        $date->year = 1899;
        $expected = array(
            '03',
            '16',
            '04',
            '02',
            '05',
            '06',
            '99',
            '1899',
            '%',
        );
        $format = '%d%n%H%n%I%n%m%n%M%n%S%n%y%n%Y%n%%';
        if (strpos(PHP_OS, 'WIN') === false) {
            $expected[] = '18';
            $expected[] = '02/03/99';
            $expected[] = ' 3';
            $expected[] = '16:05';
            $expected[] = "\t";
            $expected[] = '16:05:06';
            $format .= '%n%C%n%D%n%e%n%R%n%t%n%T';
        } else {
            $format = str_replace('%n', "\n", $format);
        }
        $this->assertEquals($expected, explode("\n", $date->strftime($format)));
    }

    public function testStrftimeDe()
    {
        if (!setlocale(LC_TIME, 'de_DE.UTF-8')) {
            $this->markTestSkipped('de_DE locale not available.');
        }

        $date = new Horde_Date('2001-02-03 16:05:06');

        if (strpos(PHP_OS, 'WIN') === false) {
            $format = '%b%n%B%n%p%n%r%n%x%n%X';
        } else {
            $format = "%b\n%B\n%p\n%x\n%X";
        }
        $this->assertEquals(strftime($format, $date->timestamp()), $date->strftime($format));
    }

    public function testStrftimeCs()
    {
        if (!function_exists('nl_langinfo')) {
            $this->markTestSkipped('nl_langinfo() not available.');
        }
        if (!setlocale(LC_TIME, 'cs_CZ.UTF-8')) {
            $this->markTestSkipped('cs_CZ locale not available.');
        }

        $date = new Horde_Date('2001-02-03 16:05:06');
        $format = nl_langinfo(D_FMT);
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
