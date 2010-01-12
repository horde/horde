--TEST--
Text_Diff: PEAR Bug #7839 ()
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer.php';

$oldtext = <<<EOT
This is line 1.
This is line 2.
This is line 3.
This is line 4.
This is line 5.
This is line 6.
This is line 7.
This is line 8.
This is line 9.
EOT;

$newtext = <<< EOT
This is line 1.
This was line 2.
This is line 3.
This is line 5.
This was line 6.
This was line 7.
This was line 8.
This is line 9.
This is line 10.
EOT;

$oldpieces = explode ("\n", $oldtext);
$newpieces = explode ("\n", $newtext);
$diff = new Text_Diff('native', array($oldpieces, $newpieces));

$renderer = new Text_Diff_Renderer();

// We need to use var_dump, as the test runner strips trailing empty lines.
echo($renderer->render($diff));
?>
--EXPECT--
2c2
< This is line 2.
---
> This was line 2.
4d3
< This is line 4.
6,8c5,7
< This is line 6.
< This is line 7.
< This is line 8.
---
> This was line 6.
> This was line 7.
> This was line 8.
9a9
> This is line 10.
