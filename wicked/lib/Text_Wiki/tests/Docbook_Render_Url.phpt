--TEST--
Text_Wiki_Docbook_Render_Url
--FILE--
<?php
require_once 'Text/Wiki.php';
$t = Text_Wiki::factory('Default', array('Url'));
print $t->transform('
[http://www.example.com/page An example page]
http://www.example.com/page
', 'Docbook');
?>
--EXPECT--
<a href="http://www.example.com/page">An example page</a>
<a href="http://www.example.com/page">http://www.example.com/page</a>
