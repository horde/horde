--TEST--
Text_Wiki_Creole_Parse_Url
--FILE--
<?php
require_once 'Text/Wiki.php';
$t = Text_Wiki::factory('Creole', array('Url'));
$t->parse('
[[http://www.example.com/page|An example page]]
[[http://www.example.com/page]]
http://www.example.com/page
', 'Creole');
var_dump($t->source);
var_dump($t->tokens);
?>
--EXPECT--
string(31) "
ÿ0ÿAn example pageÿ1ÿ
ÿ2ÿ
ÿ3ÿ
"
array(4) {
  [0]=>
  array(2) {
    [0]=>
    string(3) "Url"
    [1]=>
    array(3) {
      ["type"]=>
      string(5) "start"
      ["href"]=>
      string(27) "http://www.example.com/page"
      ["text"]=>
      string(15) "An example page"
    }
  }
  [1]=>
  array(2) {
    [0]=>
    string(3) "Url"
    [1]=>
    array(3) {
      ["type"]=>
      string(3) "end"
      ["href"]=>
      string(27) "http://www.example.com/page"
      ["text"]=>
      string(15) "An example page"
    }
  }
  [2]=>
  array(2) {
    [0]=>
    string(3) "Url"
    [1]=>
    array(1) {
      ["href"]=>
      string(27) "http://www.example.com/page"
    }
  }
  [3]=>
  array(2) {
    [0]=>
    string(3) "Url"
    [1]=>
    array(1) {
      ["href"]=>
      string(27) "http://www.example.com/page"
    }
  }
}
