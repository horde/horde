--TEST--
Test parsing of vFreeBusy information.
--FILE--
<?php

// Define BUSY periods 23.11.2006 from 5:00  to 6:00
// and 8:00 with a duration of 2 hours

$data = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//proko2//freebusy 1.0//EN
METHOD:PUBLISH
BEGIN:VFREEBUSY
ORGANIZER;CN=GunnarWrobel:MAILTO:wrobel@demo2.pardus.de
DTSTAMP:20061122T230929Z
DTSTART:20061122T230000Z
DTEND:20070121T230000Z
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

// Dump the type ("vFreebusy")
var_dump($vfb->getType());

// Dump the vfreebusy component again (the duration should be
// converted to start/end
var_dump($vfb->exportvCalendar());

// Dump organizer name ("GunnarWrobel")
var_dump($vfb->getName());

// Dump organizer mail ("wrobel@demo2.pardus.de")
var_dump($vfb->getEmail());

// Dump busy periods (array with two entries)
var_dump($vfb->getBusyPeriods());

// Decode the summary information ("testtermin")
$extra = $vfb->getExtraParams();
var_dump(base64_decode($extra[1164258000]['X-SUMMARY']));

// Dump the free periods in between the two given time stamps
var_dump($vfb->getFreePeriods(1164261500, 1164268900));

// Dump start of the free/busy information (1164236400)
var_dump($vfb->getStart());

// Dump end of the free/busy information (1164236400)
var_dump($vfb->getEnd());

// Free periods don't get added
$vfb->addBusyPeriod('FREE',1164261600,1164268800);
var_dump($vfb->getBusyPeriods());

// Add a busy period with start/end (11:00 / 12:00)
$vfb->addBusyPeriod('BUSY',1164279600,1164283200);

// Add a busy period with start/duration (14:00 / 2h)
$vfb->addBusyPeriod('BUSY',1164290400,null,7200, array('X-SUMMARY' => 'dGVzdA=='));

// Dump busy periods (array with four entries)
var_dump($vfb->getBusyPeriods());

// Dump the extra parameters (array with four entries)
var_dump($vfb->getExtraParams());

// Create new freebusy object for merging
$mfb = new Horde_Icalendar_Vfreebusy();
// 1. 3:55 / 10 minutes; summary "test4"
$mfb->addBusyPeriod('BUSY',1164254100,null,600, array('X-SUMMARY' => 'dGVzdDQ='));
// 2. 4:00 / 1 hours 5 Minutes; summary "test3"
$mfb->addBusyPeriod('BUSY',1164254400,null,3900, array('X-SUMMARY' => 'dGVzdDM='));
// 3. 5:55 / 10 minutes hours; summary "test5"
$mfb->addBusyPeriod('BUSY',1164261300,null,600, array('X-SUMMARY' => 'dGVzdDU=='));
// 4. 7:55 / 10 min
$mfb->addBusyPeriod('BUSY',1164268500,null,600);
// 5. 9:55 / 10 min
$mfb->addBusyPeriod('BUSY',1164275700,null,600);
// 6. 11:00 / 4 hours; summary "test2"
$mfb->addBusyPeriod('BUSY',1164279600,null,14400, array('X-SUMMARY' => 'dGVzdDI='));
// 7. 14:00 / 2 min
$mfb->addBusyPeriod('BUSY',1164290400,null,120);
// 8. 14:30 / 5 min; summary "test3"
$mfb->addBusyPeriod('BUSY',1164292200,null,300, array('X-SUMMARY' => 'dGVzdDM='));
// 9. 15:55 / 5 min
$mfb->addBusyPeriod('BUSY',1164297300,1164297600);

// Dump busy periods (array with seven entries)
var_dump($mfb->getBusyPeriods());

$mfb->setAttribute('DTSTART', 1004297300);
$mfb->setAttribute('DTEND', 1014297300);

// Merge freebusy components without simplification
$vfb->merge($mfb, false);

var_dump($vfb->getAttribute('DTSTART'));
var_dump($vfb->getAttribute('DTEND'));

// Dump merged periods (array with eleven entries since there
// are some entries having the same start time -> merged to
// longer of the two)
$busy = $vfb->getBusyPeriods();
$extra = $vfb->getExtraParams();
var_dump($busy);

// Check merging process (should have selected longer period)
// and dump extra information alongside
//   4 hours (instead of 2 hours); summary "test"
var_dump($busy[1164279600] - 1164279600);
var_dump(base64_decode($extra[1164279600]['X-SUMMARY']));
//   2 hours (instead of 2 minutes); summary "test2"
var_dump($busy[1164290400] - 1164290400);
var_dump(base64_decode($extra[1164290400]['X-SUMMARY']));

// Merge freebusy components again, simplify this time
$vfb->merge($mfb);

// Dump merged periods (array with five entries)
$busy =  $vfb->getBusyPeriods();
$extra = $vfb->getExtraParams();

// 1. 3:55 / 10 Minutes / test4
print "Start:" . $vfb->_exportDateTime(1164254100) . " End:" . $vfb->_exportDateTime($busy[1164254100]) . " Summary:" . base64_decode($extra[1164254100]['X-SUMMARY']) . "\n";
// 2. 4:05 / 1 hour / test3
print "Start:" . $vfb->_exportDateTime(1164254700) . " End:" . $vfb->_exportDateTime($busy[1164254700]) . " Summary:" . base64_decode($extra[1164254700]['X-SUMMARY']) . "\n";
// 3. 5:05 / 55 Minutes / testtermin
print "Start:" . $vfb->_exportDateTime(1164258300) . " End:" . $vfb->_exportDateTime($busy[1164258300]) . " Summary:" . base64_decode($extra[1164258300]['X-SUMMARY']) . "\n";
// 4. 6:00 / 5 Minutes / test5
print "Start:" . $vfb->_exportDateTime(1164261600) . " End:" . $vfb->_exportDateTime($busy[1164261600]) . " Summary:" . base64_decode($extra[1164261600]['X-SUMMARY']) . "\n";
// 5. 7:55 / 2 hours 10 Minutes
print "Start:" . $vfb->_exportDateTime(1164268500) . " End:" . $vfb->_exportDateTime($busy[1164268500]) . " Summary:\n";
// 6. 11:00 / 4 hours / test2
print "Start:" . $vfb->_exportDateTime(1164279600) . " End:" . $vfb->_exportDateTime($busy[1164279600]) . " Summary:" . base64_decode($extra[1164279600]['X-SUMMARY']) . "\n";
// 7. 15:00 / 1 hour / test
print "Start:" . $vfb->_exportDateTime(1164294000) . " End:" . $vfb->_exportDateTime($busy[1164294000]) . " Summary:" . base64_decode($extra[1164294000]['X-SUMMARY']) . "\n";

?>
--EXPECT--
string(9) "vFreebusy"
string(334) "BEGIN:VFREEBUSY
ORGANIZER;CN=GunnarWrobel:MAILTO:wrobel@demo2.pardus.de
DTSTAMP:20061122T230929Z
DTSTART:20061122T230000Z
DTEND:20070121T230000Z
FREEBUSY;X-UID=MmZlNWU3NDRmMGFjNjZkNjRjZjFkZmFmYTE4NGFiZTQ=;X-SUMMARY=dGVzd
 HRlcm1pbg==:20061123T050000Z/20061123T060000Z
FREEBUSY:20061123T080000Z/20061123T100000Z
END:VFREEBUSY
"
string(12) "GunnarWrobel"
string(22) "wrobel@demo2.pardus.de"
array(2) {
  [1164258000]=>
  int(1164261600)
  [1164268800]=>
  int(1164276000)
}
string(10) "testtermin"
array(1) {
  [1164261600]=>
  int(1164268800)
}
int(1164236400)
int(1169420400)
array(2) {
  [1164258000]=>
  int(1164261600)
  [1164268800]=>
  int(1164276000)
}
array(4) {
  [1164258000]=>
  int(1164261600)
  [1164268800]=>
  int(1164276000)
  [1164279600]=>
  int(1164283200)
  [1164290400]=>
  int(1164297600)
}
array(4) {
  [1164258000]=>
  array(2) {
    ["X-UID"]=>
    string(44) "MmZlNWU3NDRmMGFjNjZkNjRjZjFkZmFmYTE4NGFiZTQ="
    ["X-SUMMARY"]=>
    string(16) "dGVzdHRlcm1pbg=="
  }
  [1164268800]=>
  array(0) {
  }
  [1164279600]=>
  array(0) {
  }
  [1164290400]=>
  array(1) {
    ["X-SUMMARY"]=>
    string(8) "dGVzdA=="
  }
}
array(9) {
  [1164254100]=>
  int(1164254700)
  [1164254400]=>
  int(1164258300)
  [1164261300]=>
  int(1164261900)
  [1164268500]=>
  int(1164269100)
  [1164275700]=>
  int(1164276300)
  [1164279600]=>
  int(1164294000)
  [1164290400]=>
  int(1164290520)
  [1164292200]=>
  int(1164292500)
  [1164297300]=>
  int(1164297600)
}
int(1004297300)
int(1169420400)
array(11) {
  [1164258000]=>
  int(1164261600)
  [1164268800]=>
  int(1164276000)
  [1164279600]=>
  int(1164294000)
  [1164290400]=>
  int(1164297600)
  [1164254100]=>
  int(1164254700)
  [1164254400]=>
  int(1164258300)
  [1164261300]=>
  int(1164261900)
  [1164268500]=>
  int(1164269100)
  [1164275700]=>
  int(1164276300)
  [1164292200]=>
  int(1164292500)
  [1164297300]=>
  int(1164297600)
}
int(14400)
string(5) "test2"
int(7200)
string(4) "test"
Start:20061123T035500Z End:20061123T040500Z Summary:test4
Start:20061123T040500Z End:20061123T050500Z Summary:test3
Start:20061123T050500Z End:20061123T060000Z Summary:testtermin
Start:20061123T060000Z End:20061123T060500Z Summary:test5
Start:20061123T075500Z End:20061123T100500Z Summary:
Start:20061123T110000Z End:20061123T150000Z Summary:test2
Start:20061123T150000Z End:20061123T160000Z Summary:test
