--TEST--
Divider Test
--FILE--
<?php

require dirname(__FILE__) . '/../../../lib/Horde/Template.php';
$template = new Horde_Template();
$template->set('a', array('a', 'b', 'c', 'd'));
echo $template->parse("<loop:a><divider:a>,</divider:a><tag:a /></loop:a>");

?>
--EXPECT--
a,b,c,d
