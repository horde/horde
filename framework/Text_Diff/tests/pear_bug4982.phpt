--TEST--
Text_Diff: PEAR Bug #4982 (wrong line breaks with inline renderer)
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer/inline.php';

$oldtext = <<<EOT
This line is different in 1.txt
EOT;

$newtext = <<<EOT
This is new !!
This line is different in 2.txt
EOT;

$oldpieces = explode("\n", $oldtext);
$newpieces = explode("\n", $newtext);
$diff = new Text_Diff('native', array($oldpieces, $newpieces));

$renderer = new Text_Diff_Renderer_inline();
echo $renderer->render($diff);
?>
--EXPECT--
<ins>This is new !!</ins>
This line is different in <del>1.txt</del><ins>2.txt</ins>
