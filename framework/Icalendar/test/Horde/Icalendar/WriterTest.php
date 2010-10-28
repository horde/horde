<?php
/**
 * @category   Horde
 * @package    Horde_Icalendar
 * @subpackage UnitTests
 * @copyright  2009 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html
 */

/**
 * @category   Horde
 * @package    Horde_Icalendar
 * @subpackage UnitTests
 */
class Horde_Icalendar_WriterTest extends Horde_Test_Case
{
    public function testEscapes()
    {
        $ical = new Horde_Icalendar_Vcalendar(array('version' => '2.0'));
        $ical->method = 'PUBLISH';
        $event1 = new Horde_Icalendar_Vevent();
        $event2 = new Horde_Icalendar_Vevent();

        $event1->uid = '20041120-8550-innerjoin-org';
        $event1->startDate = new Horde_Date(array('year' => 2005, 'month' => 5, 'mday' => 3));
        $event1->stamp = new Horde_Date(array('year' => 2004, 'month' => 11, 'mday' => 20), 'UTC');
        $event1->summary = 'Escaped Comma in Description Field';
        $event1->description = 'There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?';

        $event2->uid = '20041120-8549-innerjoin-org';
        $event2->startDate = new Horde_Date(array('year' => 2005, 'month' => 5, 'mday' => 4));
        $event2->stamp = new Horde_Date(array('year' => 2004, 'month' => 11, 'mday' => 20), 'UTC');
        $event2->summary = 'Dash (rather than Comma) in the Description Field';
        $event2->description = 'There are important words after this dash - see anything here or have the words gone?';

        $ical->components[] = $event1;
        $ical->components[] = $event2;

        $this->assertEquals('BEGIN:VCALENDAR
METHOD:PUBLISH
PRODID:-//The Horde Project//Horde_Icalendar Library//EN
VERSION:2.0
BEGIN:VEVENT
UID:20041120-8550-innerjoin-org
DTSTART;VALUE=DATE:20050503
DTSTAMP:20041120T000000Z
SUMMARY:Escaped Comma in Description Field
DESCRIPTION:There is a comma (escaped with a baskslash) in this sentence an
 d some important words after it\, see anything here?
END:VEVENT
BEGIN:VEVENT
UID:20041120-8549-innerjoin-org
DTSTART;VALUE=DATE:20050504
DTSTAMP:20041120T000000Z
SUMMARY:Dash (rather than Comma) in the Description Field
DESCRIPTION:There are important words after this dash - see anything here o
 r have the words gone?
END:VEVENT
END:VCALENDAR
',
                            $ical->export());
    }

}