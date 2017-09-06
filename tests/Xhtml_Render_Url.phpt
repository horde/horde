--TEST--
Text_Wiki_Xhtml_Render_Url
--FILE--
<?php
require_once 'Text/Wiki.php';
$t = Text_Wiki::factory('Default', array('Url'));
$t->setRenderConf('Xhtml', 'Url', 'target', '');
print $t->transform('
[http://www.example.com/page An example page]
http://www.example.com/page
', 'Xhtml');
?>
--EXPECT--
<a href="http://www.example.com/page">An example page</a>
<a href="http://www.example.com/page">http://www.example.com/page</a>
