--TEST--
Text_Wiki_Docbook_Render_Url
--FILE--
<?php
require_once 'Text/Wiki/Creole.php';
$w = new Text_Wiki_Creole(array('Url'));
var_dump($w->transform('
[[http://www.example.com/page|An example page]]
[[http://www.example.com/page]]
http://www.example.com/page
', 'Docbook'));
?>
--EXPECT--
string(145) "
<a href="http://www.example.com/page">An example page</a>
<a href="http://www.example.com/page"></a>
<a href="http://www.example.com/page"></a>
"
