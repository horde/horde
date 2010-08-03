--TEST--
Tests the date parsing in iCalendar.php
--FILE--
<?php

$data = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//proko2//freebusy 1.0//EN
METHOD:PUBLISH
BEGIN:VFREEBUSY
ORGANIZER;CN=GunnarWrobel:MAILTO:wrobel@demo2.pardus.de
DTSTAMP:20061122T230929Z
DTSTART:19700101T000000Z
DTEND:BORKED
FREEBUSY;X-UID=MmZlNWU3NDRmMGFjNjZkNjRjZjFkZmFmYTE4NGFiZTQ=;
 X-SUMMARY=dGVzdHRlcm1pbg==:20061123T050000Z/20061123T060000Z
FREEBUSY:20061123T080000Z/PT2H
END:VFREEBUSY
END:VCALENDAR';

require_once dirname(__FILE__) . '/common.php';
$ical = new Horde_Icalendar();

// Parse the data
$ical->parseVCalendar($data);

// Get the vFreeBusy component
$vfb = $ical->getComponent(0);

var_dump($vfb->getAttribute('DTSTART'));
var_dump($vfb->getAttribute('DTEND'));

?>
--EXPECT--
int(0)
string(6) "BORKED"
