--TEST--
JSON objects tests.
--FILE--
<?php

error_reporting(E_ALL);
require dirname(__FILE__) . '/../../../lib/Horde/Serialize.php';

function out($str)
{
    echo "$str\n";
}

$obj_j = '{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}';

$obj1->car1->color = 'tan';
$obj1->car1->model = 'sedan';
$obj1->car2->color = 'red';
$obj1->car2->model = 'sports';
$obj1_j = '{"car1":{"color":"tan","model":"sedan"},"car2":{"color":"red","model":"sports"}}';

/* Types test */

// checking whether decoded type is object
out(gettype(Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON)));

/* Encode test */

// object - strict: Object with nested objects
out(Horde_Serialize::serialize($obj1, Horde_Serialize::JSON));

/* Decode/encode test */

// object case
out(Horde_Serialize::serialize(Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON), Horde_Serialize::JSON));

?>
--EXPECT--
object
{"car1":{"color":"tan","model":"sedan"},"car2":{"color":"red","model":"sports"}}
{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}
