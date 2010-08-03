--TEST--
Ensure parameters are correctly quoted.
--FILE--
<?php

require_once dirname(__FILE__) . '/common.php';
$ical = new Horde_Icalendar();
$readIcal = new Horde_Icalendar();

$event1 = Horde_Icalendar::newComponent('vevent', $ical);

$event1->setAttribute('UID', '20041120-8550-innerjoin-org');
$event1->setAttribute('DTSTART', array('year' => 2005, 'month' => 5, 'mday' => 3), array('VALUE' => 'DATE'));
$event1->setAttribute('DTSTAMP', array('year' => 2004, 'month' => 11, 'mday' => 20), array('VALUE' => 'DATE'));
$event1->setAttribute('SUMMARY', 'Escaped Comma in Description Field');
$event1->setAttribute('DESCRIPTION', 'There is a comma (escaped with a baskslash) in this sentence and some important words after it, see anything here?');
$event1->setAttribute('ORGANIZER', 'mailto:mueller@example.org', array('CN' => "Klä,rc\"hen;\n Mül:ler"));

$ical->addComponent($event1);

echo $ical->exportVCalendar();

$readIcal->parseVCalendar($ical->exportVCalendar());
$event1 = $readIcal->getComponent(0);
$attr = $event1->getAttribute('ORGANIZER', true);
echo $attr[0]['CN'];
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
ORGANIZER;CN="Klä,rchen; Mül:ler":mailto:mueller@example.org
END:VEVENT
END:VCALENDAR
Klä,rchen; Mül:ler
