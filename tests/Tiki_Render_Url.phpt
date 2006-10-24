--TEST--
Text_Wiki_Tiki_Render_Url
--FILE--
<?php
error_reporting(E_ALL ^ E_NOTICE);
include 'config.php';
require_once 'Text/Wiki/Creole.php';
$w =& new Text_Wiki_Creole(array('Url'));
var_dump($w->transform('
[[http://www.example.com/page|An example page]]
[[http://www.example.com/page]]
http://www.example.com/page
', 'Tiki'));
?>
--EXPECT--
string(107) "
[http://www.example.com/page|An example page]
[http://www.example.com/page]
[http://www.example.com/page]
"