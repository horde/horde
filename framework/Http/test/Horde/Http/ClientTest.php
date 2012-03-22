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
class Horde_Http_ClientTest extends Horde_Test_Case
{
    public function testGetTimeout()
    {
        $request = new Horde_Http_Request_Mock();
        $this->assertEquals(5, $request->timeout);
    }

    public function testSetTimeout()
    {
        $request = new Horde_Http_Request_Mock();
        $client = new Horde_Http_Client(
            array('request' => $request)
        );
        $client->{'request.timeout'} = 10;
        $this->assertEquals(10, $request->timeout);
    }

    /**
     * @expectedException Horde_Http_Exception
     */
    public function testSetUnknownOption()
    {
        $request = new Horde_Http_Request_Mock();
        $client = new Horde_Http_Client(
            array('request' => $request)
        );
        $client->timeout = 10;
    }
}
