<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Http_CurlTest extends Horde_Test_Case
{
    private $_server;

    public function setUp()
    {
        if (!function_exists('curl_exec')) {
            $this->markTestSkipped('Missing PHP extension "curl"!');
        }

        $config = self::getConfig('HTTP_TEST_CONFIG');
        if ($config && !empty($config['http']['server'])) {
            $this->_server = $config['http']['server'];
        }
    }

    /**
     * @expectedException Horde_Http_Exception
     */
    public function testThrowsOnBadUri()
    {
        $client = new Horde_Http_Client(array('request' => new Horde_Http_Request_Curl()));
        $client->get('http://doesntexist/');
    }

    /**
     * @expectedException Horde_Http_Exception
     */
    public function testThrowsOnInvalidProxyType()
    {
        $client = new Horde_Http_Client(
            array(
                'request' => new Horde_Http_Request_Curl(
                    array(
                        'proxyServer' => 'localhost',
                        'proxyType' => Horde_Http::PROXY_SOCKS4
                    )
                )
            )
        );
        $client->get('http://www.example.com/');
    }

    public function testReturnsResponseInsteadOfExceptionOn404()
    {
        $this->_skipMissingConfig();
        $client = new Horde_Http_Client(array('request' => new Horde_Http_Request_Curl()));
        $response = $client->get('http://' . $this->_server . '/doesntexist');
        $this->assertEquals(404, $response->code);
    }

    public function testGetBodyAfter404()
    {
        $this->_skipMissingConfig();
        $client = new Horde_Http_Client(array('request' => new Horde_Http_Request_Curl()));
        $response = $client->get('http://' . $this->_server . '/doesntexist');
        $content = $response->getBody();
        $this->assertTrue(!empty($content));
    }

    private function _skipMissingConfig()
    {
        if (empty($this->_server)) {
            $this->markTestSkipped('Missing configuration!');
        }
    }
}
