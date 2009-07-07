--TEST--
Horde_Mime::uudecode() test
--FILE--
<?php

require dirname(__FILE__) . '/../../../lib/Horde/Mime.php';

$encode_1 = convert_uuencode("Test string");
$encode_2 = convert_uuencode("2nd string");

$data = <<<EOF

Ignore this text.

begin 644 test.txt
$encode_1
end

More text to ignore.

begin 644 test2.txt
$encode_2
end
EOF;

var_dump(Horde_Mime::uudecode($data));

?>
--EXPECT--
array(2) {
  [0]=>
  array(3) {
    ["data"]=>
    string(11) "Test string"
    ["name"]=>
    string(8) "test.txt"
    ["perm"]=>
    string(3) "644"
  }
  [1]=>
  array(3) {
    ["data"]=>
    string(10) "2nd string"
    ["name"]=>
    string(9) "test2.txt"
    ["perm"]=>
    string(3) "644"
  }
}
