--TEST--
Horde_File_Csv: test for Bug #4025
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Csv.php';

$file = dirname(__FILE__) . '/bug_4025.csv';

// Explicit conf since we can't detect these settings. Might be able
// to improve auto-detection, but it definitely should work with the
// settings specified explicitly.
// var_dump(Horde_File_Csv::discoverFormat($file));
$conf['crlf'] = "\r\n";
$conf['sep'] = ',';
$conf['fields'] = 22;
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
  array(22) {
    [0]=>
    string(7) "Betreff"
    [1]=>
    string(10) "Beginnt am"
    [2]=>
    string(10) "Beginnt um"
    [3]=>
    string(8) "Endet am"
    [4]=>
    string(8) "Endet um"
    [5]=>
    string(20) "Ganztägiges Ereignis"
    [6]=>
    string(18) "Erinnerung Ein/Aus"
    [7]=>
    string(13) "Erinnerung am"
    [8]=>
    string(13) "Erinnerung um"
    [9]=>
    string(19) "Besprechungsplanung"
    [10]=>
    string(24) "Erforderliche Teilnehmer"
    [11]=>
    string(20) "Optionale Teilnehmer"
    [12]=>
    string(22) "Besprechungsressourcen"
    [13]=>
    string(24) "Abrechnungsinformationen"
    [14]=>
    string(12) "Beschreibung"
    [15]=>
    string(10) "Kategorien"
    [16]=>
    string(3) "Ort"
    [17]=>
    string(9) "Priorität"
    [18]=>
    string(6) "Privat"
    [19]=>
    string(14) "Reisekilometer"
    [20]=>
    string(15) "Vertraulichkeit"
    [21]=>
    string(21) "Zeitspanne zeigen als"
  }
  [1]=>
  array(22) {
    [0]=>
    string(23) "Burger Download Session"
    [1]=>
    string(8) "2.5.2006"
    [2]=>
    string(8) "11:50:00"
    [3]=>
    string(8) "2.5.2006"
    [4]=>
    string(8) "13:00:00"
    [5]=>
    string(3) "Aus"
    [6]=>
    string(3) "Ein"
    [7]=>
    string(8) "2.5.2006"
    [8]=>
    string(8) "11:35:00"
    [9]=>
    string(10) "Haas, Jörg"
    [10]=>
    string(12) "Kuhl, Oliver"
    [11]=>
    string(0) ""
    [12]=>
    string(0) ""
    [13]=>
    string(0) ""
    [14]=>
    string(1) "
"
    [15]=>
    string(0) ""
    [16]=>
    string(35) "Burger Upload Station (Burger King)"
    [17]=>
    string(6) "Normal"
    [18]=>
    string(3) "Aus"
    [19]=>
    string(0) ""
    [20]=>
    string(6) "Normal"
    [21]=>
    string(1) "1"
  }
}
