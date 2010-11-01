--TEST--
Iterator Test
--FILE--
<?php

$s = new ArrayObject(array('one', 'two', 'three'));
$i = new ArrayObject(array(1, 2, 3));
$a = new ArrayObject(array('one' => 'one', 'two' => 2));

require dirname(__FILE__) . '/../../../lib/Horde/Template.php';
$template = new Horde_Template(array('basepath' => dirname(__FILE__)));
$template->set('s', $s);
$template->set('i', $i);
$template->set('a', $a);
echo $template->parse("<loop:s><tag:s />,</loop:s>\n<loop:i><tag:i />,</loop:i>\n<tag:a.one />,<tag:a.two />,<tag:a />");

?>
--EXPECT--
one,two,three,
1,2,3,
one,2,<tag:a />
