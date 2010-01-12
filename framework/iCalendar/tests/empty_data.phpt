--TEST--
Empty data parsing test
--FILE--
<?php

require_once dirname(__FILE__) . '/../iCalendar.php';
$ical = new Horde_iCalendar();

$data = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//The Horde Project//Horde_iCalendar Library//EN
END:VCALENDAR';

var_export($ical->parseVCalendar($data));
echo "\n";
var_export($ical->getComponents());
echo "\n";
var_export($ical->parseVCalendar(''));
echo "\n";
var_export($ical->getComponents());
echo "\n";

?>
--EXPECT--
true
array (
)
false
array (
)
