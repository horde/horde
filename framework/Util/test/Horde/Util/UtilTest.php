<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */

class Horde_Util_UtilTest extends PHPUnit_Framework_TestCase
{
    public function testAddParameter()
    {
        $url = 'test';
        $this->assertEquals(
            'test?foo=1',
            (string)($url = Horde_Util::addParameter($url, 'foo', 1)));
        $this->assertEquals(
            'test?foo=1&amp;bar=2',
            (string)($url = Horde_Util::addParameter($url, 'bar', 2)));
        $this->assertEquals(
            'test?foo=1&amp;bar=2&amp;baz=3',
            (string)Horde_Util::addParameter($url, 'baz', 3));
        $this->assertEquals(
            'test?foo=1&amp;bar=2',
            (string)Horde_Util::addParameter('test', array('foo' => 1, 'bar' => 2)));
        $this->assertEquals(
            'test?foo=1&amp;bar=2&amp;baz=3',
            (string)Horde_Util::addParameter('test?foo=1', array('bar' => 2, 'baz' => 3)));
        $this->assertEquals(
            'test?foo=1&bar=2&baz=3',
            (string)Horde_Util::addParameter('test?foo=1&bar=2', array('baz' => 3)));
        $this->assertEquals(
            'test?foo=1&bar=3',
            (string)Horde_Util::addParameter('test?foo=1&bar=2', array('foo' => 1, 'bar' => 3)));
        $this->assertEquals(
            'test?foo=1&amp;bar=2&amp;baz=3',
            (string)Horde_Util::addParameter('test?foo=1&amp;bar=2', 'baz', 3));
        $this->assertEquals(
            'test?foo=bar%26baz',
            (string)($url = Horde_Util::addParameter('test', 'foo', 'bar&baz')));
        $this->assertEquals(
            'test?foo=bar%26baz&amp;x=y',
            (string)Horde_Util::addParameter($url, 'x', 'y'));
        $this->assertEquals(
            'test?foo=bar%26baz&x=y',
            (string)Horde_Util::addParameter($url, 'x', 'y', false));
        $this->assertEquals(
            'test?x=y&amp;foo=bar%26baz',
            (string)Horde_Util::addParameter(Horde_Util::addParameter('test', 'x', 'y'), 'foo', 'bar&baz'));
    }

    public function testRemoveParameter()
    {
        $url = 'test?foo=1&bar=2';
        $this->assertEquals(
            'test?bar=2',
            (string)Horde_Util::removeParameter($url, 'foo'));
        $this->assertEquals(
            'test?foo=1',
            (string)Horde_Util::removeParameter($url, 'bar'));
        $this->assertEquals(
            'test',
            (string)Horde_Util::removeParameter($url, array('foo', 'bar')));

        $url = 'test?foo=1&amp;bar=2';
        $this->assertEquals(
            'test?bar=2',
            (string)Horde_Util::removeParameter($url, 'foo'));
        $this->assertEquals(
            'test?foo=1',
            (string)Horde_Util::removeParameter($url, 'bar'));
        $this->assertEquals(
            'test',
            (string)Horde_Util::removeParameter($url, array('foo', 'bar')));

        $url = 'test?foo=1&bar=2&baz=3';
        $this->assertEquals(
            'test?bar=2&baz=3',
            (string)Horde_Util::removeParameter($url, 'foo'));

        $url = 'test?foo=1&amp;bar=2&amp;baz=3';
        $this->assertEquals(
            'test?bar=2&amp;baz=3',
            (string)Horde_Util::removeParameter($url, 'foo'));
    }
}
