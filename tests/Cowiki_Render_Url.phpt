--TEST--
Text_Wiki_Cowiki_Render_Url
--FILE--
<?php
require_once 'Text/Wiki/Creole.php';
$w = new Text_Wiki_Creole(array('Url'));
var_dump($w->transform('
[[http://www.example.com/page|An example page]]
[[http://www.example.com/page]]
http://www.example.com/page
', 'Cowiki'));
?>
--EXPECT--
string(106) "
((http://www.example.com/page)(An example page))
http://www.example.com/page
http://www.example.com/page
"
