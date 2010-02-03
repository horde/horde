--TEST--
Horde_File_Csv Test Case 005: Mac EOL
--FILE--
<?php
/**
 * Test for:
 * - Horde_File_Csv::discoverFormat()
 * - Horde_File_Csv::readQuoted()
 */

require_once dirname(__FILE__) . '/../../../lib/Horde/Csv.php';

$file = dirname(__FILE__) . '/005.csv';
$conf = Horde_File_Csv::discoverFormat($file);
//$conf['fields'] = 4;

print "Format:\n";
print_r($conf);
print "\n";

$data = array();
while ($res = Horde_File_Csv::readQuoted($file, $conf)) {
    $data[] = $res;
}

print "Data:\n";
print_r($data);
?>
--EXPECT--
Format:
Array
(
    [crlf] => 
    [fields] => 4
    [sep] => ,
    [quote] => "
)

Data:
Array
(
    [0] => Array
        (
            [0] => Field 1-1
            [1] => Field 1-2
            [2] => Field 1-3
            [3] => Field 1-4
        )

    [1] => Array
        (
            [0] => Field 2-1
            [1] => Field 2-2
            [2] => Field 2-3
            [3] => I'm multiline
Field
        )

    [2] => Array
        (
            [0] => Field 3-1
            [1] => Field 3-2
            [2] => Field 3-3
            [3] => 
        )

)
