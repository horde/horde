--TEST--
Read data with escaped values test.
--FILE--
<?php

require_once dirname(__FILE__) . '/common.php';
$ical = new Horde_Icalendar();

$data = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//innerjoin.org//NONSGML Innerjoin Events Publisher V1.0//EN
BEGIN:VEVENT
UID:20041120-8550-innerjoin-org
DTSTART;VALUE=DATE:20050503
DTSTAMP;VALUE=DATE:20041120
URL:
 http://www.innerjoin.org/iCalendar/test-cases/sunbird-0-2/ical-escaped-comma-desc.txt
SUMMARY:
 Escaped Comma in Description Field
DESCRIPTION:
 There is a comma (escaped with a baskslash) in this sentence and some important words after it\, see anything here?
END:VEVENT
BEGIN:VEVENT
UID:20041120-8549-innerjoin-org
DTSTART;VALUE=DATE:20050504
DTSTAMP;VALUE=DATE:20041120
URL:
 http://www.innerjoin.org/iCalendar/test-cases/sunbird-0-2/ical-dash-desc.txt
SUMMARY:
 Dash (rather than Comma) in the Description Field
DESCRIPTION:
 There are important words after this dash - see anything here or have the words gone?
ORGANIZER;SENT-BY="mailto
 :a@b.c":mailto:a@b.c
END:VEVENT
END:VCALENDAR';

$ical->parseVCalendar($data);
$event1 = $ical->getComponent(0);
$event2 = $ical->getComponent(1);

var_dump($event1->getAttributeValues('DESCRIPTION'),
         $event2->getAttributeValues('DESCRIPTION'),
         $event2->getAttributeValues('ORGANIZER'));

?>
--EXPECT--
array(1) {
  [0]=>
  string(114) "There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?"
}
array(1) {
  [0]=>
  string(85) "There are important words after this dash - see anything here or have the words gone?"
}
array(1) {
  [0]=>
  string(12) "mailto:a@b.c"
}
