--TEST--
Horde_File_Csv: multiline tests
--FILE--
<?php

require dirname(__FILE__) . '/common.php';
test_csv('multiline1');

?>
--EXPECT--
array(4) {
  [0]=>
  array(3) {
    [0]=>
    string(3) "one"
    [1]=>
    string(3) "two"
    [2]=>
    string(10) "three
four"
  }
  [1]=>
  array(3) {
    [0]=>
    string(4) "five"
    [1]=>
    string(9) "six
seven"
    [2]=>
    string(5) "eight"
  }
  [2]=>
  array(3) {
    [0]=>
    string(4) "nine"
    [1]=>
    string(3) "ten"
    [2]=>
    string(14) "eleven 
twelve"
  }
  [3]=>
  array(3) {
    [0]=>
    string(3) "one"
    [1]=>
    string(3) "two"
    [2]=>
    string(11) "three
 four"
  }
}
