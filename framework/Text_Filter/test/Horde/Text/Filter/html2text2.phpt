--TEST--
Horde_Text_Filter_Html2text lists test
--FILE--
<?php

require_once 'Horde/String.php';
require_once 'Horde/Util.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Html2text.php';

$html = <<<EOT
<ul>
  <li>This is a short line.</li>
  <li>This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line.</li>
  <li>And again a short line.</li>
</ul>
EOT;
echo Horde_Text_Filter::filter($html, 'html2text', array('width' => 50));
echo Horde_Text_Filter::filter($html, 'html2text', array('wrap' => false));

?>
--EXPECT--
  * This is a short line.
  * This is a long line. This is a long line. This
is a long line. This is a long line. This is a
long line. This is a long line. This is a long
line. This is a long line. This is a long line.
This is a long line. This is a long line. This is
a long line.
  * And again a short line.



  * This is a short line.
  * This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line.
  * And again a short line.
