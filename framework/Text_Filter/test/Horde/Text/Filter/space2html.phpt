--TEST--
Horde_Text_Filter_Space2html tests
--FILE--
<?php

require dirname(__FILE__) . '/../../../lib/Horde/Text/Filter.php';
require dirname(__FILE__) . '/../../../lib/Horde/Text/Filter/space2html.php';

$spaces = array('x x', 'x  x', 'x   x', 'x	x', 'x		x');
foreach ($spaces as $space) {
    echo Horde_Text_Filter::filter($space, 'space2html', array('encode_all' => false));
    echo "\n";
    echo Horde_Text_Filter::filter($space, 'space2html', array('encode_all' => true));
    echo "\n";
}

?>
--EXPECT--
x x
x&nbsp;x
x&nbsp; x
x&nbsp;&nbsp;x
x&nbsp; &nbsp;x
x&nbsp;&nbsp;&nbsp;x
x&nbsp; &nbsp; &nbsp; &nbsp; x
x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x
x&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; x
x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x
