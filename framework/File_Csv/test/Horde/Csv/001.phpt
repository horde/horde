--TEST--
Horde_File_Csv Test Case 001: Fields count less than expected
--FILE--
<?php
/**
 * Test for:
 * - Horde_File_Csv::discoverFormat()
 * - Horde_File_Csv::readQuoted()
 */

require_once dirname(__FILE__) . '/../../../lib/Horde/Csv.php';

$file = dirname(__FILE__) . '/001.csv';
$conf = Horde_File_Csv::discoverFormat($file);
$conf['fields'] = 4;

var_dump($conf);

$data = array();
while ($res = Horde_File_Csv::readQuoted($file, $conf)) {
    $data[] = $res;
}

var_dump($data);

?>
--EXPECT--
array(4) {
  ["crlf"]=>
  string(1) "
"
  ["fields"]=>
  int(4)
  ["sep"]=>
  string(1) ","
  ["quote"]=>
  string(1) """
}
array(4) {
  [0]=>
  array(4) {
    [0]=>
    string(9) "Field 1-1"
    [1]=>
    string(9) "Field 1-2"
    [2]=>
    string(9) "Field 1-3"
    [3]=>
    string(9) "Field 1-4"
  }
  [1]=>
  array(4) {
    [0]=>
    string(9) "Field 2-1"
    [1]=>
    string(9) "Field 2-2"
    [2]=>
    string(9) "Field 2-3"
    [3]=>
    string(0) ""
  }
  [2]=>
  array(4) {
    [0]=>
    string(9) "Field 3-1"
    [1]=>
    string(9) "Field 3-2"
    [2]=>
    string(0) ""
    [3]=>
    string(0) ""
  }
  [3]=>
  array(4) {
    [0]=>
    string(9) "Field 4-1"
    [1]=>
    string(0) ""
    [2]=>
    string(0) ""
    [3]=>
    string(0) ""
  }
}
