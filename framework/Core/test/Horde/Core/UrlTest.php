<?php
/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Core
 * @subpackage UnitTests
 */
class Horde_Core_UrlTest extends PHPUnit_Framework_TestCase
{
    public function testUrl()
    {
        $expected = array(
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php',
            '/hordeurl/test.php?PHPSESSID',
            'http://example.com/hordeurl/test.php',
            'http://example.com/hordeurl/test.php?PHPSESSID',
            'http://example.com/hordeurl/test.php',
            'http://example.com/hordeurl/test.php?PHPSESSID',
            'http://example.com:443/hordeurl/test.php',
            'http://example.com:443/hordeurl/test.php?PHPSESSID',
            'http://example.com:443/hordeurl/test.php',
            'http://example.com:443/hordeurl/test.php?PHPSESSID',
            'https://example.com:80/hordeurl/test.php',
            'https://example.com:80/hordeurl/test.php?PHPSESSID',
            'https://example.com:80/hordeurl/test.php',
            'https://example.com:80/hordeurl/test.php?PHPSESSID',
            'https://example.com/hordeurl/test.php',
            'https://example.com/hordeurl/test.php?PHPSESSID',
            'https://example.com/hordeurl/test.php',
            'https://example.com/hordeurl/test.php?PHPSESSID',
            'http://example.com/hordeurl/test.php',
            'http://example.com/hordeurl/test.php?PHPSESSID',
            'http://example.com/hordeurl/test.php',
            'http://example.com/hordeurl/test.php?PHPSESSID',
            'http://example.com/hordeurl/test.php',
            'http://example.com/hordeurl/test.php?PHPSESSID',
            'http://example.com/hordeurl/test.php',
            'http://example.com/hordeurl/test.php?PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1',
            '/hordeurl/test.php?foo=1&amp;PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1',
            'http://example.com/hordeurl/test.php?foo=1&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1',
            'http://example.com/hordeurl/test.php?foo=1&PHPSESSID',
            'http://example.com:443/hordeurl/test.php?foo=1',
            'http://example.com:443/hordeurl/test.php?foo=1&PHPSESSID',
            'http://example.com:443/hordeurl/test.php?foo=1',
            'http://example.com:443/hordeurl/test.php?foo=1&PHPSESSID',
            'https://example.com:80/hordeurl/test.php?foo=1',
            'https://example.com:80/hordeurl/test.php?foo=1&PHPSESSID',
            'https://example.com:80/hordeurl/test.php?foo=1',
            'https://example.com:80/hordeurl/test.php?foo=1&PHPSESSID',
            'https://example.com/hordeurl/test.php?foo=1',
            'https://example.com/hordeurl/test.php?foo=1&PHPSESSID',
            'https://example.com/hordeurl/test.php?foo=1',
            'https://example.com/hordeurl/test.php?foo=1&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1',
            'http://example.com/hordeurl/test.php?foo=1&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1',
            'http://example.com/hordeurl/test.php?foo=1&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1',
            'http://example.com/hordeurl/test.php?foo=1&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1',
            'http://example.com/hordeurl/test.php?foo=1&PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'https://example.com/hordeurl/test.php?foo=1&bar=2',
            'https://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'https://example.com/hordeurl/test.php?foo=1&bar=2',
            'https://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'https://example.com/hordeurl/test.php?foo=1&bar=2',
            'https://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'https://example.com/hordeurl/test.php?foo=1&bar=2',
            'https://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3',
            '/hordeurl/test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2&baz=3',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2&baz=3',
            'http://example.com:443/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2&baz=3',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2&baz=3',
            'https://example.com:80/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'https://example.com/hordeurl/test.php?foo=1&bar=2&baz=3',
            'https://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'https://example.com/hordeurl/test.php?foo=1&bar=2&baz=3',
            'https://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3',
            'http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID',
        );

        $uris = array(
            'test.php',
            'test.php?foo=1',
            'test.php?foo=1&bar=2',
            'test.php?foo=1&amp;bar=2',
            'test.php?foo=1&amp;bar=2&amp;baz=3'
        );
        $fulls = array(false, true);
        $ssls = array(0, 1, 3);
        $ports = array(80, 443);
        $expect = 0;
        $GLOBALS['registry'] = new Registry();
        $GLOBALS['conf']['server']['name'] = 'example.com';

        foreach ($uris as $uri) {
            foreach ($fulls as $full) {
                foreach ($ssls as $ssl) {
                    $GLOBALS['conf']['use_ssl'] = $ssl;
                    foreach ($ports as $port) {
                        $GLOBALS['conf']['server']['port'] = $port;
                        $this->assertEquals($expected[$expect++], (string)Horde::url($uri, $full, array('append_session' => -1)), sprintf('URI: %s, full: %s, SSL: %s, port: %d, session: -1', $uri, var_export($full, true), $ssl, $port));
                        unset($_COOKIE[session_name()]);
                        $this->assertEquals($expected[$expect++], (string)Horde::url($uri, $full, array('append_session' => 0)), sprintf('URI: %s, full: %s, SSL: %s, port: %d, session: 0, cookie: false', $uri, var_export($full, true), $ssl, $port));
                        $_COOKIE[session_name()] = array();
                        $this->assertEquals($expected[$expect++], (string)Horde::url($uri, $full, array('append_session' => 0)), sprintf('URI: %s, full: %s, SSL: %s, port: %d, session: 0, cookie: true', $uri, var_export($full, true), $ssl, $port));
                        $this->assertEquals($expected[$expect++], (string)Horde::url($uri, $full, array('append_session' => 1)), sprintf('URI: %s, full: %s, SSL: %s, port: %d, session: 1, cookie: true', $uri, var_export($full, true), $ssl, $port));
                    }
                }
            }
        }
    }

    public function testSelfUrl()
    {
        $GLOBALS['registry'] = new Registry();
        $GLOBALS['browser'] = new Browser();
        $GLOBALS['conf']['server']['name'] = 'example.com';
        $GLOBALS['conf']['server']['port'] = 80;
        $GLOBALS['conf']['use_ssl'] = 3;
        $_COOKIE[session_name()] = 'foo';

        // Simple script access.
        $_SERVER['SCRIPT_NAME'] = '/hordeurl/test.php';
        $_SERVER['QUERY_STRING'] = '';
        $this->assertEquals('/hordeurl/test.php', (string)Horde::selfUrl());
        $this->assertEquals('/hordeurl/test.php', (string)Horde::selfUrl(true));
        $this->assertEquals('http://example.com/hordeurl/test.php', (string)Horde::selfUrl(true, false, true));
        $this->assertEquals('https://example.com/hordeurl/test.php', (string)Horde::selfUrl(true, false, true, true));

        // No SCRIPT_NAME.
        unset($_SERVER['SCRIPT_NAME']);
        $_SERVER['PHP_SELF'] = '/hordeurl/test.php';
        $_SERVER['QUERY_STRING'] = '';
        $this->assertEquals('/hordeurl/test.php', (string)Horde::selfUrl());

        // With parameters.
        $_SERVER['SCRIPT_NAME'] = '/hordeurl/test.php';
        $_SERVER['QUERY_STRING'] = 'foo=bar&x=y';
        $this->assertEquals('/hordeurl/test.php', (string)Horde::selfUrl());
        $this->assertEquals('/hordeurl/test.php?foo=bar&amp;x=y', (string)Horde::selfUrl(true));
        $this->assertEquals('http://example.com/hordeurl/test.php?foo=bar&x=y', (string)Horde::selfUrl(true, false, true));
        $this->assertEquals('https://example.com/hordeurl/test.php?foo=bar&x=y', (string)Horde::selfUrl(true, false, true, true));

        // index.php script name.
        $_SERVER['SCRIPT_NAME'] = '/hordeurl/index.php';
        $_SERVER['QUERY_STRING'] = 'foo=bar&x=y';
        $this->assertEquals('/hordeurl/', (string)Horde::selfUrl());
        $this->assertEquals('/hordeurl/?foo=bar&amp;x=y', (string)Horde::selfUrl(true));
        $this->assertEquals('http://example.com/hordeurl/?foo=bar&x=y', (string)Horde::selfUrl(true, false, true));

        // Directory access.
        $_SERVER['SCRIPT_NAME'] = '/hordeurl/';
        $_SERVER['QUERY_STRING'] = 'foo=bar&x=y';
        $this->assertEquals('/hordeurl/', (string)Horde::selfUrl());
        $this->assertEquals('/hordeurl/?foo=bar&amp;x=y', (string)Horde::selfUrl(true));
        $this->assertEquals('http://example.com/hordeurl/?foo=bar&x=y', (string)Horde::selfUrl(true, false, true));

        // Path info.
        $_SERVER['REQUEST_URI'] = '/hordeurl/test.php/foo/bar?foo=bar&x=y';
        $_SERVER['SCRIPT_NAME'] = '/hordeurl/test.php';
        $_SERVER['QUERY_STRING'] = 'foo=bar&x=y';
        $this->assertEquals('/hordeurl/test.php', (string)Horde::selfUrl());
        $this->assertEquals('/hordeurl/test.php/foo/bar?foo=bar&amp;x=y', (string)Horde::selfUrl(true));
        $this->assertEquals('http://example.com/hordeurl/test.php/foo/bar?foo=bar&x=y', (string)Horde::selfUrl(true, false, true));

        // URL rewriting.
        $_SERVER['REQUEST_URI'] = '/hordeurl/test/foo/bar?foo=bar&x=y';
        $_SERVER['SCRIPT_NAME'] = '/hordeurl/test/index.php';
        $_SERVER['QUERY_STRING'] = 'foo=bar&x=y';
        $this->assertEquals('/hordeurl/test/', (string)Horde::selfUrl());
        $this->assertEquals('/hordeurl/test/foo/bar?foo=bar&amp;x=y', (string)Horde::selfUrl(true));
        $this->assertEquals('http://example.com/hordeurl/test/foo/bar?foo=bar&x=y', (string)Horde::selfUrl(true, false, true));
        $_SERVER['REQUEST_URI'] = '/hordeurl/foo/bar?foo=bar&x=y';
        $_SERVER['SCRIPT_NAME'] = '/hordeurl/test.php';
        $this->assertEquals('/hordeurl/test.php', (string)Horde::selfUrl());
        $this->assertEquals('/hordeurl/foo/bar?foo=bar&amp;x=y', (string)Horde::selfUrl(true));
    }
}

class Registry {
    public function get()
    {
        return '/hordeurl';
    }
}

class Browser {
    public function hasQuirk()
    {
        return false;
    }
}
