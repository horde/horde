<?php
$html = false;
$parser = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'BBcode';
$render = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : 'Xhtml';
if (!isset($_SERVER['argv'][3]) or !is_readable($sou = $_SERVER['argv'][3])) {
	die("Enter a text file to be processed as 3d argument\n First and second are parser and renderer\n");
}
$source = file_get_contents ( $sou, $html);

// load the class file
require_once('../Text/Wiki/'.$parser.'.php');
$class = 'Text_Wiki_'.$parser;

// instantiate a Text_Wiki object from the given class
$wiki =& new $class();

// when rendering XHTML, make sure wiki links point to a
// specific base URL
//$wiki->setRenderConf('xhtml', 'wikilink', 'view_url',
// 'http://example.com/view.php?page=');

// set an array of pages that exist in the wiki
// and tell the XHTML renderer about them
//$pages = array('HomePage', 'AnotherPage', 'SomeOtherPage');

$wiki->setRenderConf('xhtml', 'code', 'css_filename', 'codefilename');

// transform the wiki text into given rendering
$result = $wiki->transform($source, $render);

// display the transformed text
print( $result);




?>
