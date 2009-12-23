--TEST--
File_CSV Test Case 003: Windows EOL
--FILE--
<?php
/**
 * Test for:
 * - File_CSV::discoverFormat()
 * - File_CSV::readQuoted()
 */

require_once dirname(__FILE__) . '/../CSV.php';

$file = dirname(__FILE__) . '/003.csv';
$conf = File_CSV::discoverFormat($file);

print "Format:\n";
print_r($conf);
print "\n";

$data = array();
while ($res = File_CSV::readQuoted($file, $conf)) {
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
