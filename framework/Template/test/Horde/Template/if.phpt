--TEST--
If/Else Test
--FILE--
<?php

if (defined('E_DEPRECATED')) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

require dirname(__FILE__) . '/../../../lib/Horde/Template.php';
$template = new Horde_Template(array('basepath' => dirname(__FILE__)));
$template->set('foo', true, true);
$template->set('bar', false, true);
$template->set('baz', 'baz', true);
echo $template->fetch('/if.html');

?>
--EXPECT--
foo

false
baz
