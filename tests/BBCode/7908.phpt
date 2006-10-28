--TEST--
Text_Wiki_BBCode_Parse_List
--FILE--
<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

require_once 'Text/Wiki.php';
$t = Text_Wiki::factory('BBCode', array('List'));

$t->parse(

'[b]Services Provided:[/b]
[list]
[*]Matters pertinent to co-habitation and non-marital relationships,
including tax, property and probate matters.
[*]Guidance on how to deal with the breakdown or dissolution of a
marriage and other relationships.
[*]Advice on commencing and enabling judicial separation proceedings.
[*]Guidance through the process involved in initiating divorce
proceedings following the breakdown of a marriage.
[*]Practical guidance on financial settlements, property disputes,
maintenance rights and other financial orders arising from divorce and
separation.
[*]Advice on matters relating to children, including custody, contact
and residence rights and disputes.
[/list]',
 'BBCode');

echo "---Text---\n";
var_dump(str_replace($t->delim, '<@>', $t->source));

echo "---Tokens---\n";
var_dump($t->tokens);
?>
--EXPECT--
---Text---
string(757) "[b]Services Provided:[/b]
<@>12<@><@>0<@>Matters pertinent to co-habitation and non-marital relationships,<@>1<@>including tax, property and probate matters.
<@>2<@>Guidance on how to deal with the breakdown or dissolution of a<@>3<@>marriage and other relationships.
<@>4<@>Advice on commencing and enabling judicial separation proceedings.<@>5<@><@>6<@>Guidance through the process involved in initiating divorce<@>7<@>proceedings following the breakdown of a marriage.
<@>8<@>Practical guidance on financial settlements, property disputes,<@>9<@>maintenance rights and other financial orders arising from divorce and
separation.
<@>10<@>Advice on matters relating to children, including custody, contact<@>11<@>and residence rights and disputes.
<@>13<@>"
---Tokens---
array(14) {
  [0]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(17) "bullet_item_start"
      ["level"]=>
      int(0)
      ["count"]=>
      int(0)
    }
  }
  [1]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(15) "bullet_item_end"
      ["level"]=>
      int(0)
      ["count"]=>
      int(0)
    }
  }
  [2]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(17) "bullet_item_start"
      ["level"]=>
      int(0)
      ["count"]=>
      int(1)
    }
  }
  [3]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(15) "bullet_item_end"
      ["level"]=>
      int(0)
      ["count"]=>
      int(1)
    }
  }
  [4]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(17) "bullet_item_start"
      ["level"]=>
      int(0)
      ["count"]=>
      int(2)
    }
  }
  [5]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(15) "bullet_item_end"
      ["level"]=>
      int(0)
      ["count"]=>
      int(2)
    }
  }
  [6]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(17) "bullet_item_start"
      ["level"]=>
      int(0)
      ["count"]=>
      int(3)
    }
  }
  [7]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(15) "bullet_item_end"
      ["level"]=>
      int(0)
      ["count"]=>
      int(3)
    }
  }
  [8]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(17) "bullet_item_start"
      ["level"]=>
      int(0)
      ["count"]=>
      int(4)
    }
  }
  [9]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(15) "bullet_item_end"
      ["level"]=>
      int(0)
      ["count"]=>
      int(4)
    }
  }
  [10]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(17) "bullet_item_start"
      ["level"]=>
      int(0)
      ["count"]=>
      int(5)
    }
  }
  [11]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["type"]=>
      string(15) "bullet_item_end"
      ["level"]=>
      int(0)
      ["count"]=>
      int(5)
    }
  }
  [12]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["level"]=>
      int(0)
      ["count"]=>
      int(5)
      ["type"]=>
      string(17) "bullet_list_start"
    }
  }
  [13]=>
  array(2) {
    [0]=>
    string(4) "List"
    [1]=>
    array(3) {
      ["level"]=>
      int(0)
      ["count"]=>
      int(5)
      ["type"]=>
      string(15) "bullet_list_end"
    }
  }
}
