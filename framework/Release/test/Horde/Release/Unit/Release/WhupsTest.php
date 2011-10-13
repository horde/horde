<?php
/**
 * Test the whups accessor.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Release
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the whups accessor.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Release
 */
class Horde_Release_Unit_Release_WhupsTest
extends Horde_Release_TestCase
{
    public function setUp()
    {
        // Horde_Rpc is not E_STRICT clean
        $this->old_errorreporting = error_reporting(E_ALL & ~E_STRICT);
    }

    public function tearDown()
    {
        error_reporting($this->old_errorreporting);
    }

    public function testGetQueueId()
    {
        $response = new stdClass;
        $response->result = array('kronolith', 'horde');
        $whups = $this->_createWhups($response);
        $this->assertEquals(1, $whups->getQueueId('horde'));
    }

    public function testGetMissingQueueId()
    {
        $response = new stdClass;
        $response->result = array('kronolith', 'horde');
        $whups = $this->_createWhups($response);
        $this->assertFalse($whups->getQueueId('missing'));
    }

    /**
     * @expectedException Horde_Exception
     */
    public function testAddNewVersionOnMissingQueue()
    {
        $response = new stdClass;
        $response->result = array('kronolith', 'horde');
        $whups = $this->_createWhups($response);
        $this->assertFalse($whups->addNewVersion('missing', '1.0.1'));
    }

    public function testAddNewVersion()
    {
        $response = new stdClass;
        $response->result = array('kronolith', 'horde');
        $whups = $this->_createWhups($response);
        $this->assertNull($whups->addNewVersion('horde', '1.0.1'));
    }

    private function _createWhups($response)
    {
        return new Horde_Release_Whups(
            array(
                'url' => '',
                'client' => $this->_getClient(
                    json_encode($response)
                )
            )
        );
    }

    private function _getClient($string, $code = 200)
    {
        $body = new Horde_Support_StringStream($string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $response->code = $code;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return new Horde_Http_Client(array('request' => $request));
    }
}
