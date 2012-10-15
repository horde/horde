<?php
/**
 * Require our basic test case definition
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */
class Horde_Util_UtilTest extends PHPUnit_Framework_TestCase
{
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

    public function testDispelMagicQuotes()
    {
        Horde_Util_Test::setMagicQuotes(false);
        $vars = $expected = array('foobar', 'foo\bar', 'foo\\bar', 'foo\"bar');
        foreach ($vars as $key => $var) {
            $this->assertEquals($expected[$key], Horde_Util_Test::dispelMagicQuotes($var));
            $this->assertEquals($expected[$key], Horde_Util_Test::dispelMagicQuotes($var));
        }
        foreach ($vars as $key => $var) {
            $var = array($var);
            $this->assertEquals(array($expected[$key]), Horde_Util_Test::dispelMagicQuotes($var));
            $this->assertEquals(array($expected[$key]), Horde_Util_Test::dispelMagicQuotes($var));
        }

        Horde_Util_Test::setMagicQuotes(true);
        $vars = array('foobar', 'foo\bar', 'foo\\\\bar', 'foo\"bar');
        $expected = array('foobar', 'foobar', 'foo\bar', 'foo"bar');
        foreach ($vars as $key => $var) {
            $this->assertEquals($expected[$key], Horde_Util_Test::dispelMagicQuotes($var));
            $this->assertEquals($expected[$key], Horde_Util_Test::dispelMagicQuotes($var));
        }
        foreach ($vars as $key => $var) {
            $var = array($var);
            $this->assertEquals(array($expected[$key]), Horde_Util_Test::dispelMagicQuotes($var));
            $this->assertEquals(array($expected[$key]), Horde_Util_Test::dispelMagicQuotes($var));
        }
    }
}

class Horde_Util_Test extends Horde_Util
{
    static public function setMagicQuotes($set)
    {
        self::$_magicquotes = $set;
    }
}
