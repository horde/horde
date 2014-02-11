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
class Horde_Icalendar_TimezonesTest extends Horde_Test_Case
{
    public function setUp()
    {
        date_default_timezone_set('UTC');
    }

    public function testFiles()
    {
        $test_files = glob(__DIR__ . '/fixtures/vTimezone/*.ics');
        foreach ($test_files as $file) {
            $result = '';
            $ical = new Horde_Icalendar();
            $ical->parsevCalendar(file_get_contents($file));
            foreach ($ical->getComponents() as $component) {
                if ($component->getType() != 'vEvent') {
                    continue;
                }
                $date = $component->getAttribute('DTSTART');
                if (is_array($date)) {
                    continue;
                }
                $result .= str_replace("\r", '', $component->getAttribute('SUMMARY')) . "\n";
                $d = new Horde_Date($date);
                $result .= $d->format('H:i') . "\n";
            }
            $this->assertStringEqualsFile(
                __DIR__ . '/fixtures/vTimezone/' . basename($file, 'ics') . 'txt',
                $result,
                'Failed parsing file ' . basename($file));
        }
    }

    public function testBug12843LocalTimezoneNotTheSame()
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

    public function testBug12843LocalTimezoneTheSame()
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
