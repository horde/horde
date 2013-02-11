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

    public function testMkTimeOnTravis()
    {
        for ($last = 1, $year = 1970; $year > 1700; $year -= 10) {
            $this->assertLessThanOrEqual(0, mktime(0, 0, 0, 1, 1, $year), "Year $year failed");
            $this->assertLessThan($last, mktime(0, 0, 0, 1, 1, $year), "Year $year failed");
            $last = $year;
        }
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
}
