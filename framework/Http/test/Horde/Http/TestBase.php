<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Unit test base.
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Http_TestBase extends Horde_Test_Case
{
    protected $_server;

    protected static $_requestClass;

    public static function setUpBeforeClass()
    {
        preg_match('/Horde_Http_(.*)Test/', get_called_class(), $match);
        self::$_requestClass = 'Horde_Http_Request_' . $match[1];
    }

    public function setUp()
    {
        $config = self::getConfig('HTTP_TEST_CONFIG');
        if ($config && !empty($config['http']['server'])) {
            $this->_server = $config['http']['server'];
        }
    }

    public function testRequest()
    {
        $this->_skipMissingConfig();
        $client = new Horde_Http_Client(
            array('request' => new self::$_requestClass())
        );
        $response = $client->get('http://' . $this->_server);

        $this->assertStringStartsWith('http', $response->uri);
        $this->assertStringStartsWith('1.', $response->httpVersion);
        $this->assertEquals(200, $response->code);
        $this->assertInternalType('array', $response->headers);
        $this->assertInternalType('string', $response->getBody());
        $this->assertGreaterThan(0, strlen($response->getBody()));
        $this->assertInternalType('resource', $response->getStream());
        $this->assertStringMatchesFormat(
            '%s/%s',
            $response->getHeader('Content-Type')
        );
        $this->assertEquals(
            $response->getHeader('content-type'),
            $response->getHeader('Content-Type')
        );
        $this->assertArrayHasKey('content-type', $response->headers);
        $this->assertEquals(
            $response->getHeader('content-type'),
            $response->headers['content-type']
        );
    }

    /**
     * @expectedException Horde_Http_Exception
     */
    public function testThrowsOnBadUri()
    {
        $client = new Horde_Http_Client(
            array('request' => new self::$_requestClass())
        );
        $client->get('http://doesntexist/');
    }

    /**
     * @expectedException Horde_Http_Exception
     */
    public function testThrowsOnInvalidProxyType()
    {
        $client = new Horde_Http_Client(
            array(
                'request' => new self::$_requestClass(
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
        $client = new Horde_Http_Client(
            array('request' => new self::$_requestClass())
        );
        $response = $client->get('http://' . $this->_server . '/doesntexist');
        $this->assertEquals(404, $response->code);
    }

    public function testGetBodyAfter404()
    {
        $this->_skipMissingConfig();
        $client = new Horde_Http_Client(
            array('request' => new self::$_requestClass())
        );
        $response = $client->get('http://' . $this->_server . '/doesntexist');
        $content = $response->getBody();
        $this->assertGreaterThan(0, strlen($content));
    }

    protected function _skipMissingConfig()
    {
        if (empty($this->_server)) {
            $this->markTestSkipped('Missing configuration!');
        }
    }
}
