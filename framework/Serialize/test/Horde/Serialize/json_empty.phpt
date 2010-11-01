--TEST--
JSON empties tests.
--FILE--
<?php

error_reporting(E_ALL);
require dirname(__FILE__) . '/../../../lib/Horde/Serialize.php';

function out($str)
{
    echo "$str\n";
}

$obj0_j = '{}';
$obj1_j = '{ }';
$obj2_j = '{ /* comment inside */ }';

/* Types tests */

// should be object
out(gettype(Horde_Serialize::unserialize($obj0_j, Horde_Serialize::JSON)));
// should be empty object
out(count(get_object_vars(Horde_Serialize::unserialize($obj0_j, Horde_Serialize::JSON))));

// should be object, even with space
out(gettype(Horde_Serialize::unserialize($obj1_j, Horde_Serialize::JSON)));
// should be empty object, even with space
out(count(get_object_vars(Horde_Serialize::unserialize($obj1_j, Horde_Serialize::JSON))));

// should be object, despite comment
out(gettype(Horde_Serialize::unserialize($obj2_j, Horde_Serialize::JSON)));
// should be empty object, despite comment
out(count(get_object_vars(Horde_Serialize::unserialize($obj2_j, Horde_Serialize::JSON))));

?>
--EXPECT--
object
0
object
0
object
0
