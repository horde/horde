--TEST--
File_CSV: linebreak tests
--FILE--
<?php

require dirname(__FILE__) . '/common.php';
test_csv('simple_cr', 'simple_lf', 'simple_crlf', 'notrailing_lf', 'notrailing_crlf');

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
    string(4) "five"
    [2]=>
    string(3) "six"
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
    string(4) "five"
    [2]=>
    string(3) "six"
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
    string(4) "five"
    [2]=>
    string(3) "six"
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
    string(4) "five"
    [2]=>
    string(3) "six"
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
    string(4) "five"
    [2]=>
    string(3) "six"
  }
}
