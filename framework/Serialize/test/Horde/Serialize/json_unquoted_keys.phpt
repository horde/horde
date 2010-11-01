--TEST--
JSON unquoted keys tests.
--FILE--
<?php

error_reporting(E_ALL);
require dirname(__FILE__) . '/../../../lib/Horde/Serialize.php';

$ob1->{'0'} = 'tan';
$ob1->model = 'sedan';
$ob2->{'0'} = 'red';
$ob2->model = 'sports';
$arn = array($ob1, $ob2);
$arn_ja = '[{0:"tan","model":"sedan"},{"0":"red",model:"sports"}]';

$arrs->{'1'} = 'one';
$arrs->{'2'} = 'two';
$arrs->{'5'} = 'fi"ve';
$arrs_jo = '{"1":"one",2:"two","5":\'fi"ve\'}';

/* Decode tests */

// array case - strict: associative array with unquoted keys, nested
// associative arrays, and some numeric keys thrown in
// ...unless the input array has some numeric indeces, in which case the
// behavior is to degrade to a regular array
var_dump(Horde_Serialize::unserialize($arn_ja, Horde_Serialize::JSON));

// sparse numeric assoc array: associative array with unquoted keys,
// single-quoted values, numeric keys which are not fully populated in a range
// of 0 to length-1
// Test a sparsely populated numerically indexed associative array
var_dump(Horde_Serialize::unserialize($arrs_jo, Horde_Serialize::JSON));

?>
--EXPECT--
array(2) {
  [0]=>
  object(stdClass)(2) {
    [0]=>
    string(3) "tan"
    ["model"]=>
    string(5) "sedan"
  }
  [1]=>
  object(stdClass)(2) {
    [0]=>
    string(3) "red"
    ["model"]=>
    string(6) "sports"
  }
}
object(stdClass)(3) {
  [1]=>
  string(3) "one"
  [2]=>
  string(3) "two"
  [5]=>
  string(5) "fi"ve"
}
