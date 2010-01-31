--TEST--
Scalar Test
--FILE--
<?php

if (defined('E_DEPRECATED')) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

require dirname(__FILE__) . '/../../../lib/Horde/Template.php';
$template = new Horde_Template(array('basepath' => dirname(__FILE__)));
$template->set('one', 'one');
$template->set('two', 2);
echo $template->fetch('/scalar.html');

?>
--EXPECT--
one
2
