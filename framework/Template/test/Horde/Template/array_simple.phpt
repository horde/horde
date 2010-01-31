--TEST--
Simple Array Test
--FILE--
<?php

if (defined('E_DEPRECATED')) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

require dirname(__FILE__) . '/../../../lib/Horde/Template.php';
$template = new Horde_Template(array('basepath' => dirname(__FILE__)));
$template->set('string', array('one', 'two', 'three'));
$template->set('int', array(1, 2, 3));
echo $template->fetch('/array_simple.html');

?>
--EXPECT--
one
two
three

1
2
3
