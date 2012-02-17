<?php
/**
 * Test the date-time handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the date-time handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Unit_DateTest
extends PHPUnit_Framework_TestCase
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

    /**
     * @dataProvider provideUtcDates
     */
    public function testReadUtc($string_date, $epoch)
    {
        $date = Horde_Kolab_Format_Date::readUtcDateTime($string_date);
        if ($date === false) {
            $this->fail(sprintf('Failed parsing date %s!', $string_date));
        }
        $this->assertEquals($epoch, $date->format('U'));
    }

    public function provideUtcDates()
    {
        return array(
            array('2010-01-31T11:27:21Z', 1264937241),
            array('2005-12-19T02:55:23.437689Z', 1134960923),
            array('2005-12-19T02:55:23.437689809890787324Z', 1134960923),
            array('2001-06-19T11:01:23Z', 992948483),
            array('2005-12-19T02:55:23.43Z', 1134960923),
            // Leap second
            array('1998-12-31T23:59:59Z', 915148799),
            array('1998-12-31T23:59:60Z', 915148800),
            array('1999-01-01T00:00:00Z', 915148800),
            array('1999-01-01T00:00:01Z', 915148801),
            // No leap second
            array('2010-12-31T23:59:59Z', 1293839999),
            array('2010-12-31T23:59:60Z', 1293840000),
            array('2011-01-01T00:00:00Z', 1293840000),
            array('2011-01-01T00:00:01Z', 1293840001),
        );
    }

    /**
     * @dataProvider provideInvalidUtcDates
     */
    public function testReadInvalidUtc($string_date)
    {
        $this->assertFalse(
            Horde_Kolab_Format_Date::readUtcDateTime($string_date)
        );
    }

    public function provideInvalidUtcDates()
    {
        return array(
            array('A2010-01-31T11:27:21Z'),
            array('2010A-01-31T11:27:21Z'),
            array('2010-A01-31T11:27:21Z'),
            array('2010-019-31T11:27:21Z'),
            array('2010-01-331T11:27:21Z'),
            array('2010-01-33X11:27:21Z'),
            array('2010-01-30T11:27:21'),
            array('2010-01-30'),
            array('2010-01-30T'),
            array('2005-12-19T02:55:23.x437689Z'),
            array('2005-12-19T02:55:23.437689809890787324'),
            array('2001/06/19T11:01:23Z'),
            array('2005-12-19T02:55'),
        );
    }

    /**
     * @dataProvider provideUtcExportDates
     */
    public function testWriteUtc($string_date, $result)
    {
        $date = Horde_Kolab_Format_Date::readUtcDateTime($string_date);
        $this->assertEquals(
            $result,
            Horde_Kolab_Format_Date::writeUtcDateTime($date)
        );
    }

    public function provideUtcExportDates()
    {
        return array(
            array('2010-01-31T11:27:21Z', '2010-01-31T11:27:21Z'),
            array('2005-12-19T02:55:23.437689Z', '2005-12-19T02:55:23Z'),
            array('2005-12-19T02:55:23.437689809890787324Z', '2005-12-19T02:55:23Z'),
            array('2001-06-19T11:01:23Z', '2001-06-19T11:01:23Z'),
            array('2005-12-19T02:55:23.43Z', '2005-12-19T02:55:23Z'),
            // Leap second
            array('1998-12-31T23:59:59Z', '1998-12-31T23:59:59Z'),
            array('1998-12-31T23:59:60Z', '1999-01-01T00:00:00Z'),
            array('1999-01-01T00:00:00Z', '1999-01-01T00:00:00Z'),
            array('1999-01-01T00:00:01Z', '1999-01-01T00:00:01Z'),
            // No leap second
            array('2010-12-31T23:59:59Z', '2010-12-31T23:59:59Z'),
            array('2010-12-31T23:59:60Z', '2011-01-01T00:00:00Z'),
            array('2011-01-01T00:00:00Z', '2011-01-01T00:00:00Z'),
            array('2011-01-01T00:00:01Z', '2011-01-01T00:00:01Z'),
        );
    }

    /**
     * @dataProvider provideDates
     */
    public function testDate($string_date, $result)
    {
        $this->assertEquals(
            $result,
            Horde_Kolab_Format_Date::decodeDate($string_date)
        );
    }

    public function provideDates()
    {
        return array(
            array('2010-01-31', 1264892400),
            // Check mktime usage
            //array('1970-01-01', 0),
            array(false, 0),
            array('', 0),
        );
    }

    /**
     * @dataProvider provideDateTimes
     */
    public function testDateTime($string_date, $result)
    {
        $this->assertEquals(
            $result,
            Horde_Kolab_Format_Date::decodeDateTime($string_date)
        );
    }

    public function provideDateTimes()
    {
        return array(
            array('2010-01-31T00:01:10Z', 1264896070),
            array('1970-01-01T00:00:00Z', 0),
            array(false, 0),
            array('', 0),
        );
    }

    /**
     * @dataProvider provideDateOrDateTimes
     */
    public function testDateOrDateTime($string_date, $result)
    {
        $this->assertEquals(
            $result,
            Horde_Kolab_Format_Date::decodeDateOrDateTime($string_date)
        );
    }

    public function provideDateOrDateTimes()
    {
        return array(
            array('2010-01-31', 1264892400),
            // Check mktime usage
            //array('1970-01-01', 0),
            array(false, 0),
            array('', 0),
            array('2010-01-31T00:01:10Z', 1264896070),
            array('1970-01-01T00:00:00Z', 0),
            array(false, 0),
            array('', 0),
        );
    }

    /**
     * @dataProvider provideEncodeDates
     */
    public function testEncodeDate($date, $result)
    {
        $this->assertEquals(
            $result,
            Horde_Kolab_Format_Date::encodeDate($date)
        );
    }

    public function provideEncodeDates()
    {
        return array(
            array(1264892400, '2010-01-31'),
            array(0, '1970-01-01'),
        );
    }

    /**
     * @dataProvider provideEncodeDateTimes
     */
    public function testEncodeDateTime($date, $result)
    {
        $this->assertEquals(
            $result,
            Horde_Kolab_Format_Date::encodeDateTime($date)
        );
    }

    public function provideEncodeDateTimes()
    {
        return array(
            array(1264896070, '2010-01-31T00:01:10Z'),
            array(0, '1970-01-01T00:00:00Z'),
        );
    }

    public function testEncodeDateTimeFalse()
    {
        $this->assertEquals(
            20,
            strlen(Horde_Kolab_Format_Date::encodeDateTime(false))
        );
    }

    public function testEncodeDateFalse()
    {
        $this->assertEquals(
            10,
            strlen(Horde_Kolab_Format_Date::encodeDate(false))
        );
    }
}
