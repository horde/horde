<?php
/**
 * @package Serialize
 */

require_once 'Horde/Serialize.php';

// Convert a complex value to JSON notation, and send it to the
// browser.
$value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
echo Horde_Serialize::serialize($value, Horde_Serialize::JSON);
// prints: ["foo","bar",[1,2,"baz"],[3,[4]]]

// Accept incoming POST data, assumed to be in JSON notation.
var_dump(Horde_Serialize::unserialize(file_get_contents('php://stdin'), Horde_Serialize::JSON));
