--TEST--
File_CSV: test for Bug #6370
--FILE--
<?php

require_once dirname(__FILE__) . '/../CSV.php';

$file = dirname(__FILE__) . '/bug_6370.csv';

// Explicit conf since we can't detect these settings. Might be able
// to improve auto-detection, but it definitely should work with the
// settings specified explicitly.
// var_dump(File_CSV::discoverFormat($file));
$conf['crlf'] = "\n";
$conf['sep'] = ',';
$conf['fields'] = 92;
$conf['quote'] = '"';

$csv = array();
while ($row = File_CSV::read($file, $conf)) {
    if (is_a($row, 'PEAR_Error')) {
        var_dump($row);
        exit;
    }
    $csv[] = $row;
}
var_dump($csv);
$warnings = File_CSV::warning();
if (count($warnings)) {
    var_dump($warnings);
}

?>
--EXPECT--
array(2) {
  [0]=>
  array(92) {
    [0]=>
    string(5) "Title"
    [1]=>
    string(10) "First Name"
    [2]=>
    string(11) "Middle Name"
    [3]=>
    string(9) "Last Name"
    [4]=>
    string(6) "Suffix"
    [5]=>
    string(7) "Company"
    [6]=>
    string(10) "Department"
    [7]=>
    string(9) "Job Title"
    [8]=>
    string(15) "Business Street"
    [9]=>
    string(17) "Business Street 2"
    [10]=>
    string(17) "Business Street 3"
    [11]=>
    string(13) "Business City"
    [12]=>
    string(14) "Business State"
    [13]=>
    string(20) "Business Postal Code"
    [14]=>
    string(23) "Business Country/Region"
    [15]=>
    string(11) "Home Street"
    [16]=>
    string(13) "Home Street 2"
    [17]=>
    string(13) "Home Street 3"
    [18]=>
    string(9) "Home City"
    [19]=>
    string(10) "Home State"
    [20]=>
    string(16) "Home Postal Code"
    [21]=>
    string(19) "Home Country/Region"
    [22]=>
    string(12) "Other Street"
    [23]=>
    string(14) "Other Street 2"
    [24]=>
    string(14) "Other Street 3"
    [25]=>
    string(10) "Other City"
    [26]=>
    string(11) "Other State"
    [27]=>
    string(17) "Other Postal Code"
    [28]=>
    string(20) "Other Country/Region"
    [29]=>
    string(17) "Assistant's Phone"
    [30]=>
    string(12) "Business Fax"
    [31]=>
    string(14) "Business Phone"
    [32]=>
    string(16) "Business Phone 2"
    [33]=>
    string(8) "Callback"
    [34]=>
    string(9) "Car Phone"
    [35]=>
    string(18) "Company Main Phone"
    [36]=>
    string(8) "Home Fax"
    [37]=>
    string(10) "Home Phone"
    [38]=>
    string(12) "Home Phone 2"
    [39]=>
    string(4) "ISDN"
    [40]=>
    string(12) "Mobile Phone"
    [41]=>
    string(9) "Other Fax"
    [42]=>
    string(11) "Other Phone"
    [43]=>
    string(5) "Pager"
    [44]=>
    string(13) "Primary Phone"
    [45]=>
    string(11) "Radio Phone"
    [46]=>
    string(13) "TTY/TDD Phone"
    [47]=>
    string(5) "Telex"
    [48]=>
    string(7) "Account"
    [49]=>
    string(11) "Anniversary"
    [50]=>
    string(16) "Assistant's Name"
    [51]=>
    string(19) "Billing Information"
    [52]=>
    string(8) "Birthday"
    [53]=>
    string(23) "Business Address PO Box"
    [54]=>
    string(10) "Categories"
    [55]=>
    string(8) "Children"
    [56]=>
    string(16) "Directory Server"
    [57]=>
    string(14) "E-mail Address"
    [58]=>
    string(11) "E-mail Type"
    [59]=>
    string(19) "E-mail Display Name"
    [60]=>
    string(16) "E-mail 2 Address"
    [61]=>
    string(13) "E-mail 2 Type"
    [62]=>
    string(21) "E-mail 2 Display Name"
    [63]=>
    string(16) "E-mail 3 Address"
    [64]=>
    string(13) "E-mail 3 Type"
    [65]=>
    string(21) "E-mail 3 Display Name"
    [66]=>
    string(6) "Gender"
    [67]=>
    string(20) "Government ID Number"
    [68]=>
    string(5) "Hobby"
    [69]=>
    string(19) "Home Address PO Box"
    [70]=>
    string(8) "Initials"
    [71]=>
    string(18) "Internet Free Busy"
    [72]=>
    string(8) "Keywords"
    [73]=>
    string(8) "Language"
    [74]=>
    string(8) "Location"
    [75]=>
    string(14) "Manager's Name"
    [76]=>
    string(7) "Mileage"
    [77]=>
    string(5) "Notes"
    [78]=>
    string(15) "Office Location"
    [79]=>
    string(24) "Organizational ID Number"
    [80]=>
    string(20) "Other Address PO Box"
    [81]=>
    string(8) "Priority"
    [82]=>
    string(7) "Private"
    [83]=>
    string(10) "Profession"
    [84]=>
    string(11) "Referred By"
    [85]=>
    string(11) "Sensitivity"
    [86]=>
    string(6) "Spouse"
    [87]=>
    string(6) "User 1"
    [88]=>
    string(6) "User 2"
    [89]=>
    string(6) "User 3"
    [90]=>
    string(6) "User 4"
    [91]=>
    string(8) "Web Page"
  }
  [1]=>
  array(92) {
    [0]=>
    string(0) ""
    [1]=>
    string(0) ""
    [2]=>
    string(0) ""
    [3]=>
    string(0) ""
    [4]=>
    string(0) ""
    [5]=>
    string(0) ""
    [6]=>
    string(0) ""
    [7]=>
    string(0) ""
    [8]=>
    string(37) "Big Tower'", 1" Floor
123 Main Street"
    [9]=>
    string(0) ""
    [10]=>
    string(0) ""
    [11]=>
    string(0) ""
    [12]=>
    string(0) ""
    [13]=>
    string(0) ""
    [14]=>
    string(0) ""
    [15]=>
    string(0) ""
    [16]=>
    string(0) ""
    [17]=>
    string(0) ""
    [18]=>
    string(0) ""
    [19]=>
    string(0) ""
    [20]=>
    string(0) ""
    [21]=>
    string(0) ""
    [22]=>
    string(0) ""
    [23]=>
    string(0) ""
    [24]=>
    string(0) ""
    [25]=>
    string(0) ""
    [26]=>
    string(0) ""
    [27]=>
    string(0) ""
    [28]=>
    string(0) ""
    [29]=>
    string(0) ""
    [30]=>
    string(0) ""
    [31]=>
    string(0) ""
    [32]=>
    string(0) ""
    [33]=>
    string(0) ""
    [34]=>
    string(0) ""
    [35]=>
    string(0) ""
    [36]=>
    string(0) ""
    [37]=>
    string(0) ""
    [38]=>
    string(0) ""
    [39]=>
    string(0) ""
    [40]=>
    string(0) ""
    [41]=>
    string(0) ""
    [42]=>
    string(0) ""
    [43]=>
    string(0) ""
    [44]=>
    string(0) ""
    [45]=>
    string(0) ""
    [46]=>
    string(0) ""
    [47]=>
    string(0) ""
    [48]=>
    string(0) ""
    [49]=>
    string(6) "0/0/00"
    [50]=>
    string(0) ""
    [51]=>
    string(0) ""
    [52]=>
    string(6) "0/0/00"
    [53]=>
    string(0) ""
    [54]=>
    string(0) ""
    [55]=>
    string(0) ""
    [56]=>
    string(0) ""
    [57]=>
    string(0) ""
    [58]=>
    string(0) ""
    [59]=>
    string(0) ""
    [60]=>
    string(0) ""
    [61]=>
    string(0) ""
    [62]=>
    string(0) ""
    [63]=>
    string(0) ""
    [64]=>
    string(0) ""
    [65]=>
    string(0) ""
    [66]=>
    string(11) "Unspecified"
    [67]=>
    string(0) ""
    [68]=>
    string(0) ""
    [69]=>
    string(0) ""
    [70]=>
    string(0) ""
    [71]=>
    string(0) ""
    [72]=>
    string(0) ""
    [73]=>
    string(0) ""
    [74]=>
    string(0) ""
    [75]=>
    string(0) ""
    [76]=>
    string(0) ""
    [77]=>
    string(1) ""
    [78]=>
    string(0) ""
    [79]=>
    string(0) ""
    [80]=>
    string(0) ""
    [81]=>
    string(6) "Normal"
    [82]=>
    string(5) "False"
    [83]=>
    string(0) ""
    [84]=>
    string(0) ""
    [85]=>
    string(6) "Normal"
    [86]=>
    string(0) ""
    [87]=>
    string(0) ""
    [88]=>
    string(0) ""
    [89]=>
    string(0) ""
    [90]=>
    string(0) ""
    [91]=>
    string(0) ""
  }
}
