--TEST--
Kronolith_Event::toiCalendar() test.
--FILE--
<?php

require dirname(__FILE__) . '/../Application.php';
Horde_Cli::init();
Horde_Registry::appInit('kronolith', array('authentication' => 'none'));

$driver = new Kronolith_Driver();
$object = new Kronolith_Event_Sql($driver);
$object->start = new Horde_Date('2007-03-15 13:10:20');
$object->end = new Horde_Date('2007-03-15 14:20:00');
$object->creator = 'joe';
$object->uid = '20070315143732.4wlenqz3edq8@horde.org';
$object->title = 'H¸bscher Termin';
$object->description = "Schˆne Bescherung\nNew line";
$object->location = 'Allg‰u';
$object->alarm = 10;
$object->tags = array('Schˆngeistiges');
$object->recurrence = new Horde_Date_Recurrence($object->start);
$object->recurrence->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
$object->recurrence->setRecurInterval(2);
$object->recurrence->addException(2007, 3, 19);
$object->initialized = true;

$ical = new Horde_Icalendar('1.0');
$cal = $object->toiCalendar($ical);
$ical->addComponent($cal);
echo $ical->exportvCalendar() . "\n";

$ical = new Horde_Icalendar('2.0');
$cal = $object->toiCalendar($ical);
$ical->addComponent($cal);
echo $ical->exportvCalendar() . "\n";

$object->private = true;
$object->status = Kronolith::STATUS_TENTATIVE;
$object->recurrence = new Horde_Date_Recurrence($object->start);
$object->recurrence->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
$object->recurrence->setRecurInterval(1);
$object->recurrence->addException(2007, 4, 15);
$object->attendees =
    array('juergen@example.com' =>
          array('attendance' => Kronolith::PART_REQUIRED,
                'response' => Kronolith::RESPONSE_NONE,
                'name' => 'J¸rgen Doe'),
          0 =>
          array('attendance' => Kronolith::PART_OPTIONAL,
                'response' => Kronolith::RESPONSE_ACCEPTED,
                'name' => 'Jane Doe'),
          'jack@example.com' =>
          array('attendance' => Kronolith::PART_NONE,
                'response' => Kronolith::RESPONSE_DECLINED,
                'name' => 'Jack Doe'),
          'jenny@example.com' =>
          array('attendance' => Kronolith::PART_NONE,
                'response' => Kronolith::RESPONSE_TENTATIVE));

$ical = new Horde_Icalendar('1.0');
$cal = $object->toiCalendar($ical);
$ical->addComponent($cal);
echo $ical->exportvCalendar() . "\n";

$ical = new Horde_Icalendar('2.0');
$cal = $object->toiCalendar($ical);
$ical->addComponent($cal);
echo $ical->exportvCalendar() . "\n";

?>
--EXPECTF--
BEGIN:VCALENDAR
VERSION:1.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:20070315T121020Z
DTEND:20070315T132000Z
DTSTAMP:%d%d%d%d%d%d%d%dT%d%d%d%d%d%dZ
UID:20070315143732.4wlenqz3edq8@horde.org
SUMMARY:Event from 1:10pm to 2:20pm
ORGANIZER;CN=joe:mailto:joe
DESCRIPTION;ENCODING=QUOTED-PRINTABLE;CHARSET=ISO-8859-1:Sch=F6ne Bescherung=0D=0A=
New line
CATEGORIES;ENCODING=QUOTED-PRINTABLE;CHARSET=ISO-8859-1:Sch=F6ngeistiges
LOCATION;ENCODING=QUOTED-PRINTABLE;CHARSET=ISO-8859-1:Allg=E4u
CLASS:PUBLIC
STATUS:CONFIRMED
TRANSP:0
AALARM:20070315T120020Z
RRULE:D2 #0
EXDATE:20070319T121020Z
END:VEVENT
END:VCALENDAR

BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:20070315T121020Z
DTEND:20070315T132000Z
DTSTAMP:%d%d%d%d%d%d%d%dT%d%d%d%d%d%dZ
UID:20070315143732.4wlenqz3edq8@horde.org
SUMMARY:Event from 1:10pm to 2:20pm
ORGANIZER;CN=joe:mailto:joe
DESCRIPTION:Sch√∂ne Bescherung\nNew line
CATEGORIES:Sch√∂ngeistiges
LOCATION:Allg√§u
CLASS:PUBLIC
STATUS:CONFIRMED
TRANSP:OPAQUE
RRULE:FREQ=DAILY;INTERVAL=2
EXDATE:20070319T121020Z
BEGIN:VALARM
ACTION:DISPLAY
TRIGGER;VALUE=DURATION:-PT10M
END:VALARM
END:VEVENT
END:VCALENDAR

BEGIN:VCALENDAR
VERSION:1.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:20070315T121020Z
DTEND:20070315T132000Z
DTSTAMP:%d%d%d%d%d%d%d%dT%d%d%d%d%d%dZ
UID:20070315143732.4wlenqz3edq8@horde.org
SUMMARY:busy
ORGANIZER;CN=joe:mailto:joe
CLASS:PRIVATE
STATUS:TENTATIVE
TRANSP:0
ATTENDEE;EXPECT=REQUIRE;STATUS=NEEDS ACTION;RSVP=YES;ENCODING=QUOTED-PRINTABLE;CHARSET=ISO-8859-1:J=FCrgen Doe <juergen@example.com>
ATTENDEE;EXPECT=REQUEST;STATUS=ACCEPTED:Jane Doe
ATTENDEE;EXPECT=FYI;STATUS=DECLINED:Jack Doe <jack@example.com>
ATTENDEE;EXPECT=FYI;STATUS=TENTATIVE:jenny@example.com
AALARM:20070315T120020Z
RRULE:MD1 15 #0
EXDATE:20070415T111020Z
END:VEVENT
END:VCALENDAR

BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//The Horde Project//Horde iCalendar Library//EN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:20070315T121020Z
DTEND:20070315T132000Z
DTSTAMP:%d%d%d%d%d%d%d%dT%d%d%d%d%d%dZ
UID:20070315143732.4wlenqz3edq8@horde.org
SUMMARY:busy
ORGANIZER;CN=joe:mailto:joe
CLASS:PRIVATE
STATUS:TENTATIVE
TRANSP:OPAQUE
ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=J√ºrgen
  Doe:mailto:juergen@example.com
ATTENDEE;ROLE=OPT-PARTICIPANT;PARTSTAT=ACCEPTED;CN=Jane Doe:
ATTENDEE;ROLE=NON-PARTICIPANT;PARTSTAT=DECLINED;CN=Jack
  Doe:mailto:jack@example.com
ATTENDEE;ROLE=NON-PARTICIPANT;PARTSTAT=TENTATIVE:mailto:jenny@example.com
RRULE:FREQ=MONTHLY;INTERVAL=1
EXDATE:20070415T111020Z
BEGIN:VALARM
ACTION:DISPLAY
TRIGGER;VALUE=DURATION:-PT10M
END:VALARM
END:VEVENT
END:VCALENDAR
