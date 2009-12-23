--TEST--
File_CSV: quote tests
--FILE--
<?php

require dirname(__FILE__) . '/common.php';
test_csv('quote1', 'quote2', 'quote3', 'quote4', 'quote5');

?>
--EXPECT--
array(2) {
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
    string(8) "five six"
    [2]=>
    string(5) "seven"
  }
}
array(2) {
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
    string(8) "five six"
    [2]=>
    string(5) "seven"
  }
}
array(2) {
  [0]=>
  array(3) {
    [0]=>
    string(7) "one two"
    [1]=>
    string(11) "three, four"
    [2]=>
    string(4) "five"
  }
  [1]=>
  array(3) {
    [0]=>
    string(3) "six"
    [1]=>
    string(6) "seven "
    [2]=>
    string(5) "eight"
  }
}
array(2) {
  [0]=>
  array(3) {
    [0]=>
    string(7) "one two"
    [1]=>
    string(11) "three, four"
    [2]=>
    string(4) "five"
  }
  [1]=>
  array(3) {
    [0]=>
    string(3) "six"
    [1]=>
    string(6) "seven "
    [2]=>
    string(5) "eight"
  }
}
array(2) {
  [0]=>
  array(3) {
    [0]=>
    string(7) "one two"
    [1]=>
    string(11) "three, four"
    [2]=>
    string(4) "five"
  }
  [1]=>
  array(3) {
    [0]=>
    string(3) "six"
    [1]=>
    string(6) "seven "
    [2]=>
    string(5) "eight"
  }
}
