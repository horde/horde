--TEST--
PHP source viewer
--FILE--
<?php

require_once 'Horde/MIME/Viewer.php';
require_once 'Horde/MIME/Viewer/php.php';

$viewer = new MIME_Viewer_php($null);

ini_set('highlight.comment', 'comment');
ini_set('highlight.default', 'default');
ini_set('highlight.keyword', 'keyword');
ini_set('highlight.string', 'string');
ini_set('highlight.html', 'html');
echo $viewer->lineNumber(str_replace('&lt;?php&nbsp;', '', highlight_string('<?php highlight_file(__FILE__);', true)));
?>
--EXPECT--
<ol class="code-listing striped">
<li id="l1"><span class="default">highlight_file</span><span class="keyword">(</span><span class="default">__FILE__</span><span class="keyword">);</span></li>
</ol>