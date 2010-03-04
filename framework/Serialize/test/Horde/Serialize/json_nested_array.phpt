--TEST--
JSON nested arrays tests.
--FILE--
<?php

error_reporting(E_ALL);
require dirname(__FILE__) . '/../../../lib/Horde/Serialize.php';

$str1 = '[{"this":"that"}]';
$str2 = '{"this":["that"]}';
$str3 = '{"params":[{"foo":["1"],"bar":"1"}]}';
$str4 = '{"0": {"foo": "bar", "baz": "winkle"}}';
$str5 = '{"params":[{"options": {"old": [ ], "new": {"0": {"elements": {"old": [], "new": {"0": {"elementName": "aa", "isDefault": false, "elementRank": "0", "priceAdjust": "0", "partNumber": ""}}}, "optionName": "aa", "isRequired": false, "optionDesc": ""}}}}]}';

/* Decode tests */

// simple compactly-nested array
var_dump(Horde_Serialize::unserialize($str1, Horde_Serialize::JSON));
// simple compactly-nested array
var_dump(Horde_Serialize::unserialize($str2, Horde_Serialize::JSON));
// complex compactly nested array
var_dump(Horde_Serialize::unserialize($str3, Horde_Serialize::JSON));
// complex compactly nested array
var_dump(Horde_Serialize::unserialize($str4, Horde_Serialize::JSON));
// super complex compactly nested array
var_dump(Horde_Serialize::unserialize($str5, Horde_Serialize::JSON));

?>
--EXPECT--
array(1) {
  [0]=>
  object(stdClass)(1) {
    ["this"]=>
    string(4) "that"
  }
}
object(stdClass)(1) {
  ["this"]=>
  array(1) {
    [0]=>
    string(4) "that"
  }
}
object(stdClass)(1) {
  ["params"]=>
  array(1) {
    [0]=>
    object(stdClass)(2) {
      ["foo"]=>
      array(1) {
        [0]=>
        string(1) "1"
      }
      ["bar"]=>
      string(1) "1"
    }
  }
}
object(stdClass)(1) {
  [0]=>
  object(stdClass)(2) {
    ["foo"]=>
    string(3) "bar"
    ["baz"]=>
    string(6) "winkle"
  }
}
object(stdClass)(1) {
  ["params"]=>
  array(1) {
    [0]=>
    object(stdClass)(1) {
      ["options"]=>
      object(stdClass)(2) {
        ["old"]=>
        array(0) {
        }
        ["new"]=>
        object(stdClass)(1) {
          [0]=>
          object(stdClass)(4) {
            ["elements"]=>
            object(stdClass)(2) {
              ["old"]=>
              array(0) {
              }
              ["new"]=>
              object(stdClass)(1) {
                [0]=>
                object(stdClass)(5) {
                  ["elementName"]=>
                  string(2) "aa"
                  ["isDefault"]=>
                  bool(false)
                  ["elementRank"]=>
                  string(1) "0"
                  ["priceAdjust"]=>
                  string(1) "0"
                  ["partNumber"]=>
                  string(0) ""
                }
              }
            }
            ["optionName"]=>
            string(2) "aa"
            ["isRequired"]=>
            bool(false)
            ["optionDesc"]=>
            string(0) ""
          }
        }
      }
    }
  }
}
