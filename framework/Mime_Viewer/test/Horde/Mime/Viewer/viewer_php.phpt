--TEST--
PHP source viewer
--SKIPIF--
skip: Horde_Mime_Viewer has too many dependencies.
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Part.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Viewer.php';

ini_set('highlight.comment', 'comment');
ini_set('highlight.default', 'default');
ini_set('highlight.keyword', 'keyword');
ini_set('highlight.string', 'string');
ini_set('highlight.html', 'html');

$part = new Horde_Mime_Part();
$part->setType('application/x-php');
$part->setContents(str_replace('&lt;?php&nbsp;', '', highlight_string('<?php highlight_file(__FILE__);', true)));

$viewer = Horde_Mime_Viewer::factory($part);
echo $viewer->render();
?>
--EXPECT--
<ol class="code-listing striped">
<li id="l1"><span class="default">highlight_file</span><span class="keyword">(</span><span class="default">__FILE__</span><span class="keyword">);</span></li>
</ol>
