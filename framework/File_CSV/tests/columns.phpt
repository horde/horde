--TEST--
File_CSV: column count tests
--FILE--
<?php

require dirname(__FILE__) . '/common.php';
test_csv('columns1', 'columns2');

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
    string(5) "three"
  }
  [1]=>
  array(3) {
    [0]=>
    string(4) "four"
    [1]=>
    string(4) "five"
    [2]=>
    string(0) ""
  }
  [2]=>
  array(3) {
    [0]=>
    string(3) "six"
    [1]=>
    string(5) "seven"
    [2]=>
    string(5) "eight"
  }
  [3]=>
  array(3) {
    [0]=>
    string(4) "nine"
    [1]=>
    string(3) "ten"
    [2]=>
    string(6) "eleven"
  }
}
array(2) {
  [0]=>
  string(54) "Wrong number of fields in line 2. Expected 3, found 2."
  [1]=>
  string(48) "More fields found in line 4 than the expected 3."
}
array(4) {
  [0]=>
  array(3) {
    [0]=>
    string(3) "one"
    [1]=>
    string(3) "two"
    [2]=>
    string(5) "three"
  }
  [1]=>
  array(3) {
    [0]=>
    string(4) "four"
    [1]=>
    string(4) "five"
    [2]=>
    string(0) ""
  }
  [2]=>
  array(3) {
    [0]=>
    string(3) "six"
    [1]=>
    string(5) "seven"
    [2]=>
    string(5) "eight"
  }
  [3]=>
  array(3) {
    [0]=>
    string(4) "nine"
    [1]=>
    string(3) "ten"
    [2]=>
    string(6) "eleven"
  }
}
array(2) {
  [0]=>
  string(54) "Wrong number of fields in line 2. Expected 3, found 2."
  [1]=>
  string(48) "More fields found in line 4 than the expected 3."
}
