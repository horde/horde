--TEST--
Text_Wiki_Xhtml_Render_Url
--FILE--
<?php
require_once 'Text/Wiki/Creole.php';
$w =& new Text_Wiki_Creole(array('Url'));
var_dump($w->transform('
[[http://www.example.com/page|An example page]]
[[http://www.example.com/page]]
http://www.example.com/page
', 'Xhtml'));
?>
--EXPECT--
string(319) "
<a href="http://www.example.com/page" onclick="window.open(this.href, '_blank'); return false;">An example page</a>
<a href="http://www.example.com/page" onclick="window.open(this.href, '_blank'); return false;"></a>
<a href="http://www.example.com/page" onclick="window.open(this.href, '_blank'); return false;"></a>
"
