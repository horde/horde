--TEST--
JSON spaces and comments tests.
--FILE--
<?php

error_reporting(E_ALL);
require dirname(__FILE__) . '/../../../lib/Horde/Serialize.php';

$obj_j = '{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}';

$obj_js = '{"a_string": "\"he\":llo}:{world",
                "an_array":[1, 2, 3],
                "obj": {"a_number":123}}';

$obj_jc1 = '{"a_string": "\"he\":llo}:{world",
                // here is a comment, hoorah
                "an_array":[1, 2, 3],
                "obj": {"a_number":123}}';

$obj_jc2 = '/* this here is the sneetch */ "the sneetch"
                // this has been the sneetch.';

$obj_jc3 = '{"a_string": "\"he\":llo}:{world",
                /* here is a comment, hoorah */
                "an_array":[1, 2, 3 /* and here is another */],
                "obj": {"a_number":123}}';

$obj_jc4 = '{\'a_string\': "\"he\":llo}:{world",
                /* here is a comment, hoorah */
                \'an_array\':[1, 2, 3 /* and here is another */],
                "obj": {"a_number":123}}';

// Base result
var_dump(Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON));

/* Spaces tests */

// checking whether notation with spaces works
var_dump(Horde_Serialize::unserialize($obj_js, Horde_Serialize::JSON));

/* Comments tests */

// checking whether notation with single line comments works
var_dump(Horde_Serialize::unserialize($obj_jc1, Horde_Serialize::JSON));

// checking whether notation with multiline comments works
var_dump(Horde_Serialize::unserialize($obj_jc2, Horde_Serialize::JSON));
var_dump(Horde_Serialize::unserialize($obj_jc3, Horde_Serialize::JSON));

// checking whether notation with single-quotes and multiline comments works
var_dump(Horde_Serialize::unserialize($obj_jc4, Horde_Serialize::JSON));

?>
--EXPECT--
object(stdClass)(3) {
  ["a_string"]=>
  string(16) ""he":llo}:{world"
  ["an_array"]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    int(2)
    [2]=>
    int(3)
  }
  ["obj"]=>
  object(stdClass)(1) {
    ["a_number"]=>
    int(123)
  }
}
object(stdClass)(3) {
  ["a_string"]=>
  string(16) ""he":llo}:{world"
  ["an_array"]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    int(2)
    [2]=>
    int(3)
  }
  ["obj"]=>
  object(stdClass)(1) {
    ["a_number"]=>
    int(123)
  }
}
object(stdClass)(3) {
  ["a_string"]=>
  string(16) ""he":llo}:{world"
  ["an_array"]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    int(2)
    [2]=>
    int(3)
  }
  ["obj"]=>
  object(stdClass)(1) {
    ["a_number"]=>
    int(123)
  }
}
string(11) "the sneetch"
object(stdClass)(3) {
  ["a_string"]=>
  string(16) ""he":llo}:{world"
  ["an_array"]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    int(2)
    [2]=>
    int(3)
  }
  ["obj"]=>
  object(stdClass)(1) {
    ["a_number"]=>
    int(123)
  }
}
object(stdClass)(3) {
  ["a_string"]=>
  string(16) ""he":llo}:{world"
  ["an_array"]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    int(2)
    [2]=>
    int(3)
  }
  ["obj"]=>
  object(stdClass)(1) {
    ["a_number"]=>
    int(123)
  }
}
