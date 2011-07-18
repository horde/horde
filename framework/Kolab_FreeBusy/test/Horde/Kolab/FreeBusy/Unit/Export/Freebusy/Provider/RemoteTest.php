<?php
/**
 * Test the factory for the free/busy provider.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the factory for the free/busy provider.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Export_Freebusy_Provider_RemoteTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testTriggerRedirects()
    {
        $headers = $this->_trigger(array(), array('redirect' => true))
            ->getHeaders();
        $this->assertTrue(isset($headers['Location']));
    }

    public function testTriggerUrl()
    {
        $headers = $this->_trigger(array(), array('redirect' => true))
            ->getHeaders();
        $this->assertEquals(
            'https://example.com/freebusy/trigger/remote%40example.org/test.pfb',
            $headers['Location']
        );
    }

    public function testFetchUrl()
    {
        $headers = $this->_fetch(array(), array('redirect' => true))
            ->getHeaders();
        $this->assertEquals(
            'https://example.com/freebusy/remote%40example.org.ifb',
            $headers['Location']
        );
    }

    public function testExtendedTriggerUrl()
    {
        $headers = $this->_trigger(
            array('extended' => true),
            array('redirect' => true)
        )->getHeaders();
        $this->assertEquals(
            'https://example.com/freebusy/trigger/remote%40example.org/test.pxfb',
            $headers['Location']
        );
    }

    public function testExtendedFetchUrl()
    {
        $headers = $this->_fetch(
            array('extended' => true),
            array('redirect' => true)
        )->getHeaders();
        $this->assertEquals(
            'https://example.com/freebusy/remote%40example.org.xfb',
            $headers['Location']
        );
    }

    public function testPassThrough()
    {
        $headers = $this->_trigger(
            array(),
            array('http_client' => $this->_getClient())
        )->getHeaders();
        $this->assertTrue(isset($headers['X-Redirect-To']));
    }

    public function testPassThroughTriggerUrl()
    {
        $headers = $this->_trigger(
            array(), array('http_client' => $this->_getClient())
        )->getHeaders();
        $this->assertEquals(
            'https://mail%40example.org:TEST@example.com/freebusy/trigger/remote%40example.org/test.pfb',
            $headers['X-Redirect-To']
        );
    }

    public function testPassThroughFetchUrl()
    {
        $headers = $this->_fetch(
            array(), array('http_client' => $this->_getClient())
        )->getHeaders();
        $this->assertEquals(
            'https://mail%40example.org:TEST@example.com/freebusy/remote%40example.org.ifb',
            $headers['X-Redirect-To']
        );
    }

    public function testExtendedPassThroughTriggerUrl()
    {
        $headers = $this->_trigger(
            array('extended' => true),
            array('http_client' => $this->_getClient())
        )->getHeaders();
        $this->assertEquals(
            'https://mail%40example.org:TEST@example.com/freebusy/trigger/remote%40example.org/test.pxfb',
            $headers['X-Redirect-To']
        );
    }

    public function testExtendedPassThroughFetchUrl()
    {
        $headers = $this->_fetch(
            array('extended' => true),
            array('http_client' => $this->_getClient())
        )->getHeaders();
        $this->assertEquals(
            'https://mail%40example.org:TEST@example.com/freebusy/remote%40example.org.xfb',
            $headers['X-Redirect-To']
        );
    }

    private function _trigger($params = array(), $provider_params = array())
    {
        $response = new Horde_Controller_Response();
        $this->_provider($provider_params)->trigger(
            $response, $this->getRemoteOwner(), $this->_getUser(), 'test', $params
        );
        return $response;
    }

    private function _fetch($params = array(), $provider_params = array())
    {
        $response = new Horde_Controller_Response();
        $this->_provider($provider_params)->fetch(
            $response, $this->getRemoteOwner(), $this->_getUser(), $params
        );
        return $response;
    }

    private function _provider($params)
    {
        return $this->getRemoteProvider($params);
    }

    private function _getUser()
    {
        return $this->getDb()->getUser('mail@example.org', 'TEST');
    }

    private function _getClient($code = 200)
    {
        $string = 'RESPONSE';
        $body = new Horde_Support_StringStream($string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $response->code = $code;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return new Horde_Http_Client(array('request' => $request));
    }

}