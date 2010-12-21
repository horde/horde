<?php
/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Autoload.php';

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

    public function testGetPathInfo()
    {
        $this->assertEquals('', Horde_Util::getPathInfo());

        $_SERVER['SERVER_SOFTWARE'] = '';
        $_SERVER['PATH_INFO'] = '';
        $this->assertEquals('', Horde_Util::getPathInfo());

        $_SERVER['PATH_INFO'] = '/foo/bar';
        $this->assertEquals('/foo/bar', Horde_Util::getPathInfo());

        $_SERVER['SERVER_SOFTWARE'] = 'lighttpd/1.4.26';
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_URI'] = '/horde/path.php';
        $_SERVER['SCRIPT_NAME'] = '/horde/path.php';
        $this->assertEquals('', Horde_Util::getPathInfo());
        $_SERVER['REQUEST_URI'] = '/horde/path.php?baz';
        $_SERVER['QUERY_STRING'] = 'baz';
        $this->assertEquals('', Horde_Util::getPathInfo());

        $_SERVER['REQUEST_URI'] = '/horde/path.php/foo/bar';
        $_SERVER['SCRIPT_NAME'] = '/horde/path.php';
        $_SERVER['QUERY_STRING'] = '';
        $this->assertEquals('/foo/bar', Horde_Util::getPathInfo());
        $_SERVER['REQUEST_URI'] = '/horde/path.php/foo/bar?baz';
        $_SERVER['QUERY_STRING'] = 'baz';
        $this->assertEquals('/foo/bar', Horde_Util::getPathInfo());
        $_SERVER['REQUEST_URI'] = '/horde/foo/bar?baz';
        $this->assertEquals('/foo/bar', Horde_Util::getPathInfo());

        $_SERVER['REQUEST_URI'] = '/horde/';
        $_SERVER['SCRIPT_NAME'] = '/horde/index.php';
        $this->assertEquals('', Horde_Util::getPathInfo());

        $_SERVER['REQUEST_URI'] = '/horde/index.php';
        $_SERVER['SCRIPT_NAME'] = '/horde/index.php';
        $_SERVER['QUERY_STRING'] = '';
        $this->assertEquals('', Horde_Util::getPathInfo());
        $_SERVER['REQUEST_URI'] = '/horde/index.php?baz';
        $_SERVER['QUERY_STRING'] = 'baz';
        $this->assertEquals('', Horde_Util::getPathInfo());

        $_SERVER['REQUEST_URI'] = '/horde/index.php/foo/bar';
        $_SERVER['SCRIPT_NAME'] = '/horde/index.php';
        $_SERVER['QUERY_STRING'] = '';
        $this->assertEquals('/foo/bar', Horde_Util::getPathInfo());
        $_SERVER['REQUEST_URI'] = '/horde/index.php/foo/bar?baz';
        $_SERVER['QUERY_STRING'] = 'baz';
        $this->assertEquals('/foo/bar', Horde_Util::getPathInfo());

        $_SERVER['REQUEST_URI'] = '/test/42?id=42';
        $_SERVER['SCRIPT_NAME'] = '/test/index.php';
        $_SERVER['QUERY_STRING'] = 'id=42&id=42';
        $this->assertEquals('/42', Horde_Util::getPathInfo());
    }
}
