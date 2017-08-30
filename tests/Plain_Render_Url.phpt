--TEST--
Text_Wiki_Plain_Render_Url
--FILE--
<?php
require_once 'Text/Wiki.php';
$t = Text_Wiki::factory('Default', array('Url'));
print $t->transform('
[http://www.example.com/page An example page]
http://www.example.com/page
', 'Plain');
?>
--EXPECT--

An example page
http://www.example.com/page
