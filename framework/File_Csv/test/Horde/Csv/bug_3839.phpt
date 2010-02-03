--TEST--
Horde_File_Csv: test for Bug #3839
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Csv.php';

$file = dirname(__FILE__) . '/bug_3839.csv';

// Explicit conf since we can't detect these settings. Might be able
// to improve auto-detection, but it definitely should work with the
// settings specified explicitly.
// var_dump(Horde_File_Csv::discoverFormat($file));
$conf['crlf'] = "\r\n";
$conf['sep'] = '~';
$conf['fields'] = 12;
$conf['quote'] = '"';

$csv = array();
while ($row = Horde_File_Csv::read($file, $conf)) {
    if (is_a($row, 'PEAR_Error')) {
        var_dump($row);
        return;
    }
    $csv[] = $row;
}
var_dump($csv);
$warnings = Horde_File_Csv::warning();
if (count($warnings)) {
    var_dump($warnings);
}

?>
--EXPECT--
array(2) {
  [0]=>
  array(12) {
    [0]=>
    string(7) "Subject"
    [1]=>
    string(10) "Start Date"
    [2]=>
    string(10) "Start Time"
    [3]=>
    string(8) "End Date"
    [4]=>
    string(8) "End Time"
    [5]=>
    string(13) "All day event"
    [6]=>
    string(15) "Reminder on/off"
    [7]=>
    string(13) "Reminder Date"
    [8]=>
    string(13) "Reminder Time"
    [9]=>
    string(8) "Category"
    [10]=>
    string(11) "Description"
    [11]=>
    string(8) "Priority"
  }
  [1]=>
  array(12) {
    [0]=>
    string(41) "Inservice on new resource: "CPNP Toolkit""
    [1]=>
    string(10) "2004-11-08"
    [2]=>
    string(8) "10:30 AM"
    [3]=>
    string(10) "2004-11-08"
    [4]=>
    string(8) "11:30 AM"
    [5]=>
    string(5) "FALSE"
    [6]=>
    string(5) "FALSE"
    [7]=>
    string(0) ""
    [8]=>
    string(0) ""
    [9]=>
    string(8) "Training"
    [10]=>
    string(1109) "CPN Program ... 
Inservice on new resource: "CPNP Toolkit"

<b>Registration Deadline:  October 27, 2004, noon</b>

<a href="F041108A-Eval.pdf" target="_blank">
<img src="acrobat.gif" border="0"></a>  <a href="F041108A-Eval.pdf" target="_blank">  Session Evaluation - Eligibility for Prize!</a>

<a href="F041108A-DI.pdf" target="_blank">
<img src="acrobat.gif" border="0"></a>  <a href="F041108A-DI.pdf" target="_blank">  Dial In Numbers for Sites Registered</a>

<a href="F041108A.pdf" target="_blank">
<img src="acrobat.gif" border="0"></a>  <a href="F041108A.pdf" target="_blank">  Poster and Registration Form</a>

Facilitator:  Manager 

preblurb preblurb preblurb preblurb preblurb preblurb preblurb preblurb preblurb  "CPNP Toolkit".  postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb .

postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb postblurb 

Come check out the new resource!"
    [11]=>
    string(6) "Normal"
  }
}
