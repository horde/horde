--TEST--
Read/write values with proper escaping test
--FILE--
<?php

require_once __DIR__ . '/common.php';
$writeIcal = new Horde_Icalendar();
$readIcal = new Horde_Icalendar();

$event1 = Horde_Icalendar::newComponent('vevent', $writeIcal);
$event2 = Horde_Icalendar::newComponent('vevent', $writeIcal);

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

$writeIcal->addComponent($event1);
$writeIcal->addComponent($event2);

$readIcal->parseVCalendar($writeIcal->exportVCalendar());
$event3 = $readIcal->getComponent(0);
$event4 = $readIcal->getComponent(1);
var_dump($event3->getAttributeValues('DESCRIPTION'));
var_dump($event4->getAttributeValues('DESCRIPTION'));

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
