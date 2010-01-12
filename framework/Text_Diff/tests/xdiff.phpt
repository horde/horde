--TEST--
Text_Diff: Text_Diff_Engine_xdiff test.
--SKIPIF--
<?php if (!extension_loaded('xdiff')) echo 'skip xdiff extension not installed'; ?>
--FILE--
<?php

require_once 'PEAR.php';
require_once 'Text/Diff.php';
require_once 'Text/Diff/Renderer/unified.php';

$lines1 = file(dirname(__FILE__) . '/1.txt');
$lines2 = file(dirname(__FILE__) . '/2.txt');
$diff = new Text_Diff('xdiff', array($lines1, $lines2));
$renderer = new Text_Diff_Renderer_unified();
echo $renderer->render($diff);

?>
--EXPECT--
@@ -1,3 +1,3 @@
 This line is the same.
-This line is different in 1.txt
+This line is different in 2.txt
 This line is the same.
