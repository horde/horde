--TEST--
Basic aspell driver test
--SKIPIF--
<?php

$aspell = trim(`which aspell`);
if (!is_executable($aspell)) {
    $aspell = trim(`which ispell`);
}
if (!is_executable($aspell)) {
    echo 'skip No aspell/ispell binary found.';
}

--FILE--
<?php

$aspell = trim(`which aspell`);
if (!is_executable($aspell)) {
    $aspell = trim(`which ispell`);
}

require_once 'Horde/SpellChecker.php';
$speller = Horde_SpellChecker::factory('Aspell', array('path' => $aspell));
var_dump($speller->spellCheck('some tet [mispeled] ?'));

--EXPECT--
array(2) {
  ["bad"]=>
  array(2) {
    [0]=>
    string(3) "tet"
    [1]=>
    string(8) "mispeled"
  }
  ["suggestions"]=>
  array(2) {
    [0]=>
    array(10) {
      [0]=>
      string(3) "Tet"
      [1]=>
      string(4) "teat"
      [2]=>
      string(4) "tent"
      [3]=>
      string(4) "test"
      [4]=>
      string(3) "yet"
      [5]=>
      string(2) "Te"
      [6]=>
      string(2) "ET"
      [7]=>
      string(3) "Ted"
      [8]=>
      string(3) "Tut"
      [9]=>
      string(3) "tat"
    }
    [1]=>
    array(10) {
      [0]=>
      string(10) "misspelled"
      [1]=>
      string(10) "misapplied"
      [2]=>
      string(6) "misled"
      [3]=>
      string(9) "dispelled"
      [4]=>
      string(8) "misfiled"
      [5]=>
      string(8) "misruled"
      [6]=>
      string(7) "mislead"
      [7]=>
      string(7) "spelled"
      [8]=>
      string(7) "spieled"
      [9]=>
      string(9) "misplaced"
    }
  }
}
