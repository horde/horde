<?php
/**
 * Test the remote server handler.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/bsd
 * @link       http://www.horde.org/libraries/Horde_Http
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * Test the remote server handler.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/bsd
 * @link       http://www.horde.org/libraries/Horde_Http
 */
class Horde_Http_MockTest extends PHPUnit_Framework_TestCase
{
    public function testNoResponses()
    {
        $mock = new Horde_Http_Request_Mock();
        $client = new Horde_Http_Client(array('request' => $mock));
        $this->assertNull($client->get());
    }

    public function testPreparedResponse()
    {
        $body = 'Test';
        $stream = new Horde_Support_StringStream($body);
        $response = new Horde_Http_Response_Mock('', $stream->fopen());
        $mock = new Horde_Http_Request_Mock();
        $mock->setResponse($response);
        $client = new Horde_Http_Client(array('request' => $mock));
        $this->assertEquals('Test', $client->get()->getBody());
    }

    public function testAddResponseBody()
    {
        $mock = new Horde_Http_Request_Mock();
        $mock->addResponse('Test');
        $client = new Horde_Http_Client(array('request' => $mock));
        $this->assertEquals('Test', $client->get()->getBody());
    }

    public function testAddResponseCode()
    {
        $mock = new Horde_Http_Request_Mock();
        $mock->addResponse('Test', 404);
        $client = new Horde_Http_Client(array('request' => $mock));
        $this->assertEquals(404, $client->get()->code);
    }

    public function testAddResponseUri()
    {
        $mock = new Horde_Http_Request_Mock();
        $mock->addResponse('Test', 404, 'http://example.org');
        $client = new Horde_Http_Client(array('request' => $mock));
        $this->assertEquals('http://example.org', $client->get()->uri);
    }

    public function testAddResponseHeader()
    {
        $mock = new Horde_Http_Request_Mock();
        $mock->addResponse('Test', 404, 'http://example.org', array('test: TEST'));
        $client = new Horde_Http_Client(array('request' => $mock));
        $this->assertEquals('TEST', $client->get()->getHeader('test'));
    }

    public function testAddStringResponses()
    {
        $mock = new Horde_Http_Request_Mock();
        $mock->addResponses(array('A', 'B'));
        $client = new Horde_Http_Client(array('request' => $mock));
        $client->get();
        $this->assertEquals('B', $client->get()->getBody());
    }

    public function testAddArrayResponses()
    {
        $mock = new Horde_Http_Request_Mock();
        $mock->addResponses(
            array(
                array('body' => 'A'),
                array('code' => 404),
                array('uri' => 'http://example.org'),
                array('headers' => 'test: TEST'),
            )
        );
        $client = new Horde_Http_Client(array('request' => $mock));
        $this->assertEquals('A', $client->get()->getBody());
        $this->assertEquals(404, $client->get()->code);
        $this->assertEquals('http://example.org', $client->get()->uri);
        $this->assertEquals('TEST', $client->get()->getHeader('test'));
    }
}
