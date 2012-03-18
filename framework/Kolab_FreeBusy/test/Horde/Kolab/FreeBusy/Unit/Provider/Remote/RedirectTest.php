<?php
/**
 * Test the remote redirect provider.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the remote redirect provider.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Provider_Remote_RedirectTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testTriggerRedirects()
    {
        $headers = $this->_trigger()->getHeaders();
        $this->assertTrue(isset($headers['Location']));
    }

    public function testTriggerUrl()
    {
        $headers = $this->_trigger()->getHeaders();
        $this->assertEquals(
            'https://example.com/freebusy/trigger/remote%40example.org/test.pfb',
            $headers['Location']
        );
    }

    public function testFetchUrl()
    {
        $headers = $this->_fetch()->getHeaders();
        $this->assertEquals(
            'https://example.com/freebusy/remote%40example.org.ifb',
            $headers['Location']
        );
    }

    public function testExtendedTriggerUrl()
    {
        $headers = $this->_trigger(array('extended' => true))->getHeaders();
        $this->assertEquals(
            'https://example.com/freebusy/trigger/remote%40example.org/test.pxfb',
            $headers['Location']
        );
    }

    public function testExtendedFetchUrl()
    {
        $headers = $this->_fetch(array('extended' => true))->getHeaders();
        $this->assertEquals(
            'https://example.com/freebusy/remote%40example.org.xfb',
            $headers['Location']
        );
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testDeleteException()
    {
        $response = new Horde_Controller_Response();
        $this->_provider('')->delete($response);
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testRegenerateException()
    {
        $response = new Horde_Controller_Response();
        $this->_provider('')->regenerate($response);
    }

    private function _trigger($params = array())
    {
        $response = new Horde_Controller_Response();
        if (!empty($params['extended'])) {
            $path = 'trigger/remote%40example.org/test.pxfb';
        } else {
            $path = 'trigger/remote%40example.org/test.pfb';
        }
        $this->_provider($path)->trigger($response);
        return $response;
    }

    private function _fetch($params = array())
    {
        $response = new Horde_Controller_Response();
        if (!empty($params['extended'])) {
            $path = 'remote%40example.org.xfb';
        } else {
            $path = 'remote%40example.org.ifb';
        }
        $this->_provider($path)->fetch($response, $params);
        return $response;
    }

    private function _provider($path)
    {
        $request = new Horde_Controller_Request_Mock();
        $request->setPath($path);
        return new Horde_Kolab_FreeBusy_Provider_Remote_Redirect(
            $this->getRemoteOwner(), $request
        );
    }
}