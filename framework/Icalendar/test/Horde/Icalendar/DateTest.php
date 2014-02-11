<?php
/**
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 */
class Horde_Icalendar_DateTest extends Horde_Test_Case
{
    public function testDatesLocalTimezoneNotTheSame()
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('America/New_York');
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/bug12843.ics'));
        foreach ($ical->getComponents() as $component) {
            if ($component->getType() != 'vEvent') {
                continue;
            }
            $date_params = $component->getAttribute('DTSTART', true);
            $date = $component->getAttribute('DTSTART');
            $start = new Horde_Date($date, $date_params[0]['TZID']);
            $this->assertEquals(18, $start->hour);
        }
        date_default_timezone_set($tz);
    }

    public function testDatesLocalTimezoneTheSame()
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/bug12843.ics'));
        foreach ($ical->getComponents() as $component) {
            if ($component->getType() != 'vEvent') {
                continue;
            }
            $date_params = $component->getAttribute('DTSTART', true);
            $date = $component->getAttribute('DTSTART');
            $start = new Horde_Date($date, $date_params[0]['TZID']);
            $this->assertEquals(18, $start->hour);
        }
        date_default_timezone_set($tz);
    }
}
