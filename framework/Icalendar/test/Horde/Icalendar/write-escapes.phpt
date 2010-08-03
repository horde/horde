--TEST--
Write values with proper escaping test
--FILE--
<?php

require_once dirname(__FILE__) . '/common.php';
$ical = new Horde_Icalendar();

$event1 = Horde_Icalendar::newComponent('vevent', $ical);
$event2 = Horde_Icalendar::newComponent('vevent', $ical);

$event1->setAttribute('UID', '20041120-8550-innerjoin-org');
$event1->setAttribute('DTSTART', array('year' => 2005, 'month' => 5, 'mday' => 3), array('VALUE' => 'DATE'));
$event1->setAttribute('DTSTAMP', array('year' => 2004, 'month' => 11, 'mday' => 20), array('VALUE' => 'DATE'));
$event1->setAttribute('SUMMARY', 'Escaped Comma in Description Field');
$event1->setAttribute('DESCRIPTION', 'There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?');

$event2->setAttribute('UID', '20041120-8549-innerjoin-org');
$event2->setAttribute('DTSTART', array('year' => 2005, 'month' => 5, 'mday' => 4), array('VALUE' => 'DATE'));
$event2->setAttribute('DTSTAMP', array('year' => 2004, 'month' => 11, 'mday' => 20), array('VALUE' => 'DATE'));
$event2->setAttribute('SUMMARY', 'Dash (rather than Comma) in the Description Field');
$event2->setAttribute('DESCRIPTION', 'There are important words after this dash - see anything here or have the words gone?');

$ical->addComponent($event1);
$ical->addComponent($event2);

echo $ical->exportVCalendar();

?>
--EXPECT--
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
UID:20041120-8550-innerjoin-org
DTSTART;VALUE=DATE:20050503
DTSTAMP;VALUE=DATE:20041120
SUMMARY:Escaped Comma in Description Field
DESCRIPTION:There is a comma (escaped with a baskslash) in this sentence
  and some important words after it\, see anything here?
END:VEVENT
BEGIN:VEVENT
UID:20041120-8549-innerjoin-org
DTSTART;VALUE=DATE:20050504
DTSTAMP;VALUE=DATE:20041120
SUMMARY:Dash (rather than Comma) in the Description Field
DESCRIPTION:There are important words after this dash - see anything here
  or have the words gone?
END:VEVENT
END:VCALENDAR
