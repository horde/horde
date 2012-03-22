--TEST--
vCalendar 2.0 (iCalendar) test
--FILE--
<?php

$data = <<<VCARD
BEGIN:VCALENDAR
PRODID:-//Google Inc//Google Calendar 70.9054//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:PEAR - PHP Extension and Application Repository
X-WR-TIMEZONE:Atlantic/Reykjavik
X-WR-CALDESC:pear.php.net activity calendar\, bug triage\, group meetings\,
  qa\, conferences or similar
BEGIN:VEVENT
DTSTART:20081025T160000Z
DTEND:20081025T210000Z
DTSTAMP:20080819T092753Z
UID:ntnrt4go4482q2trk18bt62c0o@google.com
RECURRENCE-ID:20081025T160000Z
CLASS:PUBLIC
CREATED:20080306T002605Z
DESCRIPTION:Bug Triage session\\n\\nNot been invited ? Want to attend ? Let u
 s know and we'll add you!
LAST-MODIFIED:20080718T204006Z
LOCATION:#pear-bugs Efnet
SEQUENCE:2
STATUS:CONFIRMED
SUMMARY:Bug Triage
TRANSP:OPAQUE
CATEGORIES:foo,bar,fuz buz,blah\, blah
END:VEVENT
END:VCALENDAR
VCARD;

require_once __DIR__ . '/common.php';
$ical = new Horde_Icalendar();
$ical->parsevCalendar($data);
var_export($ical->getAllAttributes());
echo "\n";
$vevent = $ical->getComponent(0);
var_export($vevent->getAllAttributes());

?>
--EXPECT--
array (
  0 => 
  array (
    'name' => 'PRODID',
    'params' => 
    array (
    ),
    'value' => '-//Google Inc//Google Calendar 70.9054//EN',
    'values' => 
    array (
      0 => '-//Google Inc//Google Calendar 70.9054//EN',
    ),
  ),
  1 => 
  array (
    'name' => 'VERSION',
    'params' => 
    array (
    ),
    'value' => '2.0',
    'values' => 
    array (
      0 => '2.0',
    ),
  ),
  2 => 
  array (
    'name' => 'CALSCALE',
    'params' => 
    array (
    ),
    'value' => 'GREGORIAN',
    'values' => 
    array (
      0 => 'GREGORIAN',
    ),
  ),
  3 => 
  array (
    'name' => 'METHOD',
    'params' => 
    array (
    ),
    'value' => 'PUBLISH',
    'values' => 
    array (
      0 => 'PUBLISH',
    ),
  ),
  4 => 
  array (
    'name' => 'X-WR-CALNAME',
    'params' => 
    array (
    ),
    'value' => 'PEAR - PHP Extension and Application Repository',
    'values' => 
    array (
      0 => 'PEAR - PHP Extension and Application Repository',
    ),
  ),
  5 => 
  array (
    'name' => 'X-WR-TIMEZONE',
    'params' => 
    array (
    ),
    'value' => 'Atlantic/Reykjavik',
    'values' => 
    array (
      0 => 'Atlantic/Reykjavik',
    ),
  ),
  6 => 
  array (
    'name' => 'X-WR-CALDESC',
    'params' => 
    array (
    ),
    'value' => 'pear.php.net activity calendar, bug triage, group meetings, qa, conferences or similar',
    'values' => 
    array (
      0 => 'pear.php.net activity calendar, bug triage, group meetings, qa, conferences or similar',
    ),
  ),
)
array (
  0 => 
  array (
    'name' => 'DTSTART',
    'params' => 
    array (
    ),
    'value' => 1224950400,
    'values' => 
    array (
      0 => 1224950400,
    ),
  ),
  1 => 
  array (
    'name' => 'DTEND',
    'params' => 
    array (
    ),
    'value' => 1224968400,
    'values' => 
    array (
      0 => 1224968400,
    ),
  ),
  2 => 
  array (
    'name' => 'DTSTAMP',
    'params' => 
    array (
    ),
    'value' => 1219138073,
    'values' => 
    array (
      0 => 1219138073,
    ),
  ),
  3 => 
  array (
    'name' => 'UID',
    'params' => 
    array (
    ),
    'value' => 'ntnrt4go4482q2trk18bt62c0o@google.com',
    'values' => 
    array (
      0 => 'ntnrt4go4482q2trk18bt62c0o@google.com',
    ),
  ),
  4 => 
  array (
    'name' => 'RECURRENCE-ID',
    'params' => 
    array (
    ),
    'value' => 1224950400,
    'values' => 
    array (
      0 => 1224950400,
    ),
  ),
  5 => 
  array (
    'name' => 'CLASS',
    'params' => 
    array (
    ),
    'value' => 'PUBLIC',
    'values' => 
    array (
      0 => 'PUBLIC',
    ),
  ),
  6 => 
  array (
    'name' => 'CREATED',
    'params' => 
    array (
    ),
    'value' => 1204763165,
    'values' => 
    array (
      0 => 1204763165,
    ),
  ),
  7 => 
  array (
    'name' => 'DESCRIPTION',
    'params' => 
    array (
    ),
    'value' => 'Bug Triage session

Not been invited ? Want to attend ? Let us know and we\'ll add you!',
    'values' => 
    array (
      0 => 'Bug Triage session

Not been invited ? Want to attend ? Let us know and we\'ll add you!',
    ),
  ),
  8 => 
  array (
    'name' => 'LAST-MODIFIED',
    'params' => 
    array (
    ),
    'value' => 1216413606,
    'values' => 
    array (
      0 => 1216413606,
    ),
  ),
  9 => 
  array (
    'name' => 'LOCATION',
    'params' => 
    array (
    ),
    'value' => '#pear-bugs Efnet',
    'values' => 
    array (
      0 => '#pear-bugs Efnet',
    ),
  ),
  10 => 
  array (
    'name' => 'SEQUENCE',
    'params' => 
    array (
    ),
    'value' => 2,
    'values' => 
    array (
      0 => 2,
    ),
  ),
  11 => 
  array (
    'name' => 'STATUS',
    'params' => 
    array (
    ),
    'value' => 'CONFIRMED',
    'values' => 
    array (
      0 => 'CONFIRMED',
    ),
  ),
  12 => 
  array (
    'name' => 'SUMMARY',
    'params' => 
    array (
    ),
    'value' => 'Bug Triage',
    'values' => 
    array (
      0 => 'Bug Triage',
    ),
  ),
  13 => 
  array (
    'name' => 'TRANSP',
    'params' => 
    array (
    ),
    'value' => 'OPAQUE',
    'values' => 
    array (
      0 => 'OPAQUE',
    ),
  ),
  14 => 
  array (
    'name' => 'CATEGORIES',
    'params' => 
    array (
    ),
    'value' => 'foo,bar,fuz buz,blah, blah',
    'values' => 
    array (
      0 => 'foo',
      1 => 'bar',
      2 => 'fuz buz',
      3 => 'blah, blah',
    ),
  ),
)
