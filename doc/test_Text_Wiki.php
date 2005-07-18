<?php
set_include_path( realpath(dirname(__FILE__).'/../'). PATH_SEPARATOR . get_include_path() );
$parser = $render = $source = '';
$plist = array('Default', 'BBCode');
$rlist = array('Xhtml', 'Plain', 'Latex');
if ($_SERVER['PHP_SELF']) {
    $html = true;
    if (isset($_REQUEST['example'])) {
        $_REQUEST['source'] = file_get_contents ($_REQUEST['exchoice']);
        if (preg_match('#(\b'.implode('\b|\b', $plist).'\b)#i',
                         $_REQUEST['source'], $match)) {
            $_REQUEST['parser'] = $match[1];
        }
        $_REQUEST['translate'] = true;
    }
    $parser = isset($_REQUEST['parser']) ? $_REQUEST['parser'] : 'BBCode';
    $render = isset($_REQUEST['render']) ? $_REQUEST['render'] : 'Xhtml';
    $source = isset($_REQUEST['source']) ? $_REQUEST['source'] : '';
    if (!isset($_REQUEST['translate'])) {
        echo bldHtml($parser, $render, $source, '', $plist, $rlist);
        die();
    }
} else {
    $html = false;
    $parser = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'BBCode';
    $render = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : 'Xhtml';
    if (!isset($_SERVER['argv'][3]) or !is_readable($sou = $_SERVER['argv'][3])) {
    	die("Enter a text file to be processed as 3d argument\n First and second are parser and renderer\n");
    }
    $source = file_get_contents ($sou);
}
// load the class file
if ($parser != 'Default') {
    require_once 'Text/Wiki/'.$parser.'.php';
    $class = 'Text_Wiki_'.$parser;
} else  {
    require_once 'Text/Wiki.php';
    $class = 'Text_Wiki';
}

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
if ($html) {
    echo bldHtml($parser, $render, $source, $result, $plist, $rlist);
} else {
    echo $result;
}
function bldHtml($parser, $render, $source, $result, $plist, $rlist) {
    $optparser = $optrender = $optexample = '';
    foreach($plist as $opt) {
          $optparser .= "<option value='{$opt}'".
            ($opt == $parser? " selected" : "").
            ">{$opt}</option>\n";
    }
    foreach($rlist as $opt) {
          $optrender .= "<option value='{$opt}'".
            ($opt == $render? " selected" : "").
            ">{$opt}</option>\n";
    }
    $hresult = htmlentities($result);
    if ($render != 'Xhtml') {
        $result = '';
    }
    return <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
  <title>PEAR::Text_Wiki Demo</title>
  <meta name="AUTHOR" content="bertrand Gugger / Toggg">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="KEYWORDS" content="PEAR, Wiki, Parse, Render, Convert, PHP, BBCode, Xhtml, Plain, Latex">
  <!--
  <link rel="stylesheet" type="text/css" href="test_Text_Wiki.css">
  -->
  <script language="javascript" type="text/javascript">
  // <!--
  
  // -->
  </script>
</head>
<body>
<h3>PEAR::Text_Wiki Demo</h3>
<div style="float: left;">
<FORM method="post">
Translate from
<SELECT name="parser">{$optparser}</SELECT>
to
<SELECT name="render">{$optrender}</SELECT>
<br />
<textarea name="source" cols="80" rows="25">{$source}</textarea>
<br />
<INPUT type="submit" name="translate" value="translate"> or choose
<SELECT name="exchoice">{$optexample}</SELECT>
and
<INPUT type="submit" name="example" value="example">
</FORM>
</div>
<div style="float: down;">
{$hresult}
</div>
<div>
{$result}
</div>
</body>
</html>
EOT;
}
?>
