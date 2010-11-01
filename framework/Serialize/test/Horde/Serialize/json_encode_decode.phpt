--TEST--
JSON encode/decode tests.
--FILE--
<?php

error_reporting(E_ALL);
require dirname(__FILE__) . '/../../../lib/Horde/Serialize.php';

function out($str)
{
    echo "$str\n";
}

$obj = new stdClass();
$obj->a_string = '"he":llo}:{world';
$obj->an_array = array(1, 2, 3);
$obj->obj = new stdClass();
$obj->obj->a_number = 123;
$obj_j = '{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}';

$arr = array(null, true, array(1, 2, 3), "hello\"],[world!");
$arr_j = '[null,true,[1,2,3],"hello\"],[world!"]';

$str1 = 'hello world';
$str1_j = '"hello world"';
$str1_j_ = "'hello world'";

$str2 = "hello\t\"world\"";
$str2_j = '"hello\\t\\"world\\""';

$str3 = "\\\r\n\t\"/";
$str3_j = '"\\\\\\r\\n\\t\\"\\/"';

$str4 = 'héllö wørłd';
$str4_j = '"h\u00e9ll\u00f6 w\u00f8r\u0142d"';
$str4_j_ = '"héllö wørłd"';

/* Encode tests. */

// type case: null
out(Horde_Serialize::serialize(null, Horde_Serialize::JSON));
// type case: boolean true
out(Horde_Serialize::serialize(true, Horde_Serialize::JSON));
// type case: boolean false
out(Horde_Serialize::serialize(false, Horde_Serialize::JSON));

// numeric case: 1
out(Horde_Serialize::serialize(1, Horde_Serialize::JSON));
// numeric case: -1
out(Horde_Serialize::serialize(-1, Horde_Serialize::JSON));
// numeric case: 1.0
out(Horde_Serialize::serialize(1.0, Horde_Serialize::JSON));
// numeric case: 1.1
out(Horde_Serialize::serialize(1.1, Horde_Serialize::JSON));

// string case: hello world
out(Horde_Serialize::serialize($str1, Horde_Serialize::JSON));
// string case: hello world, with tab, double-quotes
out(Horde_Serialize::serialize($str2, Horde_Serialize::JSON));
// string case: backslash, return, newline, tab, double-quote
out(Horde_Serialize::serialize($str3, Horde_Serialize::JSON));
// string case: hello world, with unicode
out(Horde_Serialize::serialize($str4, Horde_Serialize::JSON));

// array case: array with elements and nested arrays
out(Horde_Serialize::serialize($arr, Horde_Serialize::JSON));
// object case: object with properties, nested object and arrays
out(Horde_Serialize::serialize($obj, Horde_Serialize::JSON));

echo"============================================================================\n";

/* Decode tests */

// type case: null
var_dump(Horde_Serialize::unserialize('null', Horde_Serialize::JSON));
// type case: boolean true
var_dump(Horde_Serialize::unserialize('true', Horde_Serialize::JSON));
// type case: boolean false
var_dump(Horde_Serialize::unserialize('false', Horde_Serialize::JSON));

// numeric case: 1
var_dump(Horde_Serialize::unserialize('1', Horde_Serialize::JSON));
// numeric case: -1
var_dump(Horde_Serialize::unserialize('-1', Horde_Serialize::JSON));
// numeric case: 1.0
var_dump(Horde_Serialize::unserialize('1.0', Horde_Serialize::JSON));
// numeric case: 1.1
var_dump(Horde_Serialize::unserialize('1.1', Horde_Serialize::JSON));

// string case: hello world
var_dump(Horde_Serialize::unserialize($str1_j, Horde_Serialize::JSON));
var_dump(Horde_Serialize::unserialize($str1_j_, Horde_Serialize::JSON));
// string case: hello world, with tab, double-quotes
var_dump(Horde_Serialize::unserialize($str2_j, Horde_Serialize::JSON));
// string case: backslash, return, newline, tab, double-quote
var_dump(Horde_Serialize::unserialize($str3_j, Horde_Serialize::JSON));
// string case: hello world, with unicode
var_dump(Horde_Serialize::unserialize($str4_j, Horde_Serialize::JSON));
var_dump(Horde_Serialize::unserialize($str4_j_, Horde_Serialize::JSON));

// array case: array with elements and nested arrays
var_dump(Horde_Serialize::unserialize($arr_j, Horde_Serialize::JSON));
// object case: object with properties, nested object and arrays
var_dump(Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON));

echo"============================================================================\n";

/* Encode-decode tests */

// type case: null
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize(null, Horde_Serialize::JSON), Horde_Serialize::JSON));
// type case: boolean true
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize(true, Horde_Serialize::JSON), Horde_Serialize::JSON));
// type case: boolean false
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize(false, Horde_Serialize::JSON), Horde_Serialize::JSON));

// numeric case: 1
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize(1, Horde_Serialize::JSON), Horde_Serialize::JSON));
// numeric case: -1
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize(-1, Horde_Serialize::JSON), Horde_Serialize::JSON));
// numeric case: 1.0
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize(1.0, Horde_Serialize::JSON), Horde_Serialize::JSON));
// numeric case: 1.1
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize(1.1, Horde_Serialize::JSON), Horde_Serialize::JSON));

// string case: hello world
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize($str1, Horde_Serialize::JSON), Horde_Serialize::JSON));
// string case: hello world, with tab, double-quotes
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize($str2, Horde_Serialize::JSON), Horde_Serialize::JSON));
// string case: backslash, return, newline, tab, double-quote
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize($str3, Horde_Serialize::JSON), Horde_Serialize::JSON));
// string case: hello world, with unicode
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize($str4, Horde_Serialize::JSON), Horde_Serialize::JSON));

// array case: array with elements and nested arrays
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize($arr, Horde_Serialize::JSON), Horde_Serialize::JSON));
// object case: object with properties, nested object and arrays
var_dump(Horde_Serialize::unserialize(Horde_Serialize::serialize($obj, Horde_Serialize::JSON), Horde_Serialize::JSON));

echo"============================================================================\n";

/* Decode-encode tests */

// type case: null
out(Horde_Serialize::serialize(Horde_Serialize::unserialize('null', Horde_Serialize::JSON), Horde_Serialize::JSON));
// type case: boolean true
out(Horde_Serialize::serialize(Horde_Serialize::unserialize('true', Horde_Serialize::JSON), Horde_Serialize::JSON));
// type case: boolean false
out(Horde_Serialize::serialize(Horde_Serialize::unserialize('false', Horde_Serialize::JSON), Horde_Serialize::JSON));

// numeric case: 1
out(Horde_Serialize::serialize(Horde_Serialize::unserialize('1', Horde_Serialize::JSON), Horde_Serialize::JSON));
// numeric case: -1
out(Horde_Serialize::serialize(Horde_Serialize::unserialize('-1', Horde_Serialize::JSON), Horde_Serialize::JSON));
// numeric case: 1.0
out(Horde_Serialize::serialize(Horde_Serialize::unserialize('1.0', Horde_Serialize::JSON), Horde_Serialize::JSON));
// numeric case: 1.1
out(Horde_Serialize::serialize(Horde_Serialize::unserialize('1.1', Horde_Serialize::JSON), Horde_Serialize::JSON));

// string case: hello world
out(Horde_Serialize::serialize(Horde_Serialize::unserialize($str1_j, Horde_Serialize::JSON), Horde_Serialize::JSON));
// string case: hello world, with tab, double-quotes
out(Horde_Serialize::serialize(Horde_Serialize::unserialize($str2_j, Horde_Serialize::JSON), Horde_Serialize::JSON));
// string case: backslash, return, newline, tab, double-quote
out(Horde_Serialize::serialize(Horde_Serialize::unserialize($str3_j, Horde_Serialize::JSON), Horde_Serialize::JSON));
// string case: hello world, with unicode
out(Horde_Serialize::serialize(Horde_Serialize::unserialize($str4_j, Horde_Serialize::JSON), Horde_Serialize::JSON));
out(Horde_Serialize::serialize(Horde_Serialize::unserialize($str4_j_, Horde_Serialize::JSON), Horde_Serialize::JSON));

// array case: array with elements and nested arrays
out(Horde_Serialize::serialize(Horde_Serialize::unserialize($arr_j, Horde_Serialize::JSON), Horde_Serialize::JSON));
// object case: object with properties, nested object and arrays
out(Horde_Serialize::serialize(Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON), Horde_Serialize::JSON));

?>
--EXPECT--
null
true
false
1
-1
1
1.1
"hello world"
"hello\t\"world\""
"\\\r\n\t\"\/"
"h\u00e9ll\u00f6 w\u00f8r\u0142d"
[null,true,[1,2,3],"hello\"],[world!"]
{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}
============================================================================
NULL
bool(true)
bool(false)
int(1)
int(-1)
float(1)
float(1.1)
string(11) "hello world"
string(11) "hello world"
string(13) "hello	"world""
string(6) "\
	"/"
string(15) "héllö wørłd"
string(15) "héllö wørłd"
array(4) {
  [0]=>
  NULL
  [1]=>
  bool(true)
  [2]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    int(2)
    [2]=>
    int(3)
  }
  [3]=>
  string(15) "hello"],[world!"
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
============================================================================
NULL
bool(true)
bool(false)
int(1)
int(-1)
int(1)
float(1.1)
string(11) "hello world"
string(13) "hello	"world""
string(6) "\
	"/"
string(15) "héllö wørłd"
array(4) {
  [0]=>
  NULL
  [1]=>
  bool(true)
  [2]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    int(2)
    [2]=>
    int(3)
  }
  [3]=>
  string(15) "hello"],[world!"
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
============================================================================
null
true
false
1
-1
1
1.1
"hello world"
"hello\t\"world\""
"\\\r\n\t\"\/"
"h\u00e9ll\u00f6 w\u00f8r\u0142d"
"h\u00e9ll\u00f6 w\u00f8r\u0142d"
[null,true,[1,2,3],"hello\"],[world!"]
{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}
