--TEST--
Bug #7423: Leading space on attribute names
--FILE--
<?php

require_once __DIR__ . '/common.php';
$ical = new Horde_Icalendar();

$data = 'BEGIN:VCALENDAR
VERSION:2.0
METHOD:PUBLISH
BEGIN:VEVENT
BEGIN:VALARM
ACTION:AUDIO
TRIGGER:PT540M
END:VALARM
SUMMARY:birthday
END:VEVENT
END:VCALENDAR';

$ical->parseVCalendar($data);
$components = $ical->getComponents();
foreach ($components as $component) {
    var_dump($component->toHash(true));
}

?>
--EXPECT--
array(1) {
  ["SUMMARY"]=>
  string(8) "birthday"
}
