--TEST--
Horde_Util::addParameter() tests
--FILE--
<?php

require_once 'Horde/Url.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Util.php';

$url = 'test';
echo ($url = Horde_Util::addParameter($url, 'foo', 1)) . "\n";
echo ($url = Horde_Util::addParameter($url, 'bar', 2)) . "\n";
echo Horde_Util::addParameter($url, 'baz', 3) . "\n";
echo Horde_Util::addParameter('test', array('foo' => 1, 'bar' => 2)) . "\n";
echo Horde_Util::addParameter('test?foo=1', array('bar' => 2, 'baz' => 3)) . "\n";
echo Horde_Util::addParameter('test?foo=1&bar=2', array('baz' => 3)) . "\n";
echo Horde_Util::addParameter('test?foo=1&bar=2', array('foo' => 1, 'bar' => 3)) . "\n";
echo Horde_Util::addParameter('test?foo=1&amp;bar=2', 'baz', 3) . "\n";
echo ($url = Horde_Util::addParameter('test', 'foo', 'bar&baz')) . "\n";
echo Horde_Util::addParameter($url, 'x', 'y') . "\n";
echo Horde_Util::addParameter($url, 'x', 'y', false) . "\n";
echo Horde_Util::addParameter(Horde_Util::addParameter('test', 'x', 'y'), 'foo', 'bar&baz') . "\n";

?>
--EXPECT--
test?foo=1
test?foo=1&amp;bar=2
test?foo=1&amp;bar=2&amp;baz=3
test?foo=1&amp;bar=2
test?foo=1&amp;bar=2&amp;baz=3
test?foo=1&bar=2&baz=3
test?foo=1&bar=3
test?foo=1&amp;bar=2&amp;baz=3
test?foo=bar%26baz
test?foo=bar%26baz&amp;x=y
test?foo=bar%26baz&x=y
test?x=y&amp;foo=bar%26baz
