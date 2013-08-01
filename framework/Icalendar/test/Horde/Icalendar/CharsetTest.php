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
class Horde_Icalendar_CharsetTest extends Horde_Test_Case
{
    public function testFiles()
    {
        $test_files = glob(__DIR__ . '/fixtures/charset*.ics');
        foreach ($test_files as $file) {
            $ical = new Horde_Icalendar();
            $ical->parsevCalendar(file_get_contents($file));
            $this->assertEquals(
                'möchen',
                $ical->getComponent(0)->getAttribute('SUMMARY')
            );
        }
    }
}
