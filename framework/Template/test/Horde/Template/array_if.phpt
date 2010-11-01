--TEST--
If Array Test
--FILE--
<?php

if (defined('E_DEPRECATED')) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

require dirname(__FILE__) . '/../../../lib/Horde/Template.php';
$template = new Horde_Template(array('basepath' => dirname(__FILE__)));
$template->set('foo', array('one', 'two', 'three'), true);
$template->set('bar', array(), true);
echo $template->fetch('/array_if.html');

?>
--EXPECT--
one
two
three

else
