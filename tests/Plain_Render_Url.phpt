--TEST--
Text_Wiki_Plain_Render_Url
--FILE--
<?php
require_once 'Text/Wiki/Creole.php';
$w = new Text_Wiki_Creole(array('Url'));
print $w->transform('
[[http://www.example.com/page|An example page]]
[[http://www.example.com/page]]
http://www.example.com/page
', 'Plain');
?>
--EXPECT--

An example page
http://www.example.com/page
http://www.example.com/page

