/**
 * @package    Horde_Stream_Filter
 * @subpackage UnitTests
 */
--TEST--
Horde_Stream_Filter_Eol:: tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Stream/Filter/Eol.php';

stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');

$test = fopen('php://temp', 'r+');
fwrite($test, "A\r\nB\rC\nD\r\n\r\nE\r\rF\n\nG\r\n\n\r\nH\r\n\r\r\nI");

foreach (array("\r", "\n", "\r\n", "") as $val) {
    $filter = stream_filter_prepend($test, 'horde_eol', STREAM_FILTER_READ, array('eol' => $val));
    rewind($test);
    fpassthru($test);
    stream_filter_remove($filter);

    echo "\n---\n";
}

fclose($test);
?>
--EXPECT--
ABCDEFGHI
---
A
B
C
D

E

F

G


H


I
---
A
B
C
D

E

F

G


H


I
---
ABCDEFGHI
---
