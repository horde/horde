--TEST--
Text_Wiki_Doku_Render_Url
--SKIPIF--
<?php require_once dirname(__FILE__).'/skipif.php'; ?>
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
', 'Doku'));
?>
--EXPECT--
string(105) "
[[http://www.example.com/page|An example page]]
http://www.example.com/page
http://www.example.com/page
"
