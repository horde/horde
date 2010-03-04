--TEST--
JSON associative arrays tests.
--FILE--
<?php

error_reporting(E_ALL);
require dirname(__FILE__) . '/../../../lib/Horde/Serialize.php';

function out($str)
{
    echo "$str\n";
}

$arr = array('car1'=> array('color'=> 'tan', 'model' => 'sedan'),
             'car2' => array('color' => 'red', 'model' => 'sports'));
$arr_jo = '{"car1":{"color":"tan","model":"sedan"},"car2":{"color":"red","model":"sports"}}';

$arn = array(0 => array(0 => 'tan\\', 'model\\' => 'sedan'), 1 => array(0 => 'red', 'model' => 'sports'));
$arn_ja = '[{"0":"tan\\\\","model\\\\":"sedan"},{"0":"red","model":"sports"}]';

$arrs = array(1 => 'one', 2 => 'two', 5 => 'five');
$arrs_jo = '{"1":"one","2":"two","5":"five"}';

/* Types test */

// strict type should be array
out(gettype(Horde_Serialize::unserialize($arn_ja, Horde_Serialize::JSON)));

echo"============================================================================\n";

/* Encode tests */

// array case - strict: associative array with nested associative arrays
out(Horde_Serialize::serialize($arr, Horde_Serialize::JSON));

// array case - strict: associative array with nested associative arrays, and
// some numeric keys thrown in
// Should degrade to a numeric array.
out(Horde_Serialize::serialize($arn, Horde_Serialize::JSON));

// sparse numeric assoc array: associative array numeric keys which are not
// fully populated in a range of 0 to length-1
// Test a sparsely populated numerically indexed associative array.
out(Horde_Serialize::serialize($arrs, Horde_Serialize::JSON));

?>
--EXPECT--
array
============================================================================
{"car1":{"color":"tan","model":"sedan"},"car2":{"color":"red","model":"sports"}}
[{"0":"tan\\","model\\":"sedan"},{"0":"red","model":"sports"}]
{"1":"one","2":"two","5":"five"}
