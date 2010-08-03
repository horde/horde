--TEST--
GEO test
--FILE--
<?php

require_once dirname(__FILE__) . '/common.php';
$ical = new Horde_Icalendar();

$data = 'BEGIN:VCARD
VERSION:2.1
GEO:37.24,-17.87
END:VCARD';

$ical->parseVCalendar($data);
$vcard = $ical->getComponent(0);
var_export($vcard->getAttribute('GEO'));
echo "\n";

$data = 'BEGIN:VCARD
VERSION:3.0
GEO:37.386013;-122.082932
END:VCARD';

$ical->parseVCalendar($data);
$vcard = $ical->getComponent(0);
var_export($vcard->getAttribute('GEO'));

?>
--EXPECT--
array (
  'latitude' => -17.87,
  'longitude' => 37.24,
)
array (
  'latitude' => 37.386013,
  'longitude' => -122.082932,
)
