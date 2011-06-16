<?php
/**
 * Test the remote server handler.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the package contents.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_RemoteTest
extends Horde_Pear_TestCase
{
    public function testListPackages()
    {
        $this->assertType(
            'array',
            $this->_getRemote()->listPackages()
        );
    }

    public function testListPackagesContainsComponents()
    {
        $this->assertEquals(
            array('A', 'B'),
            $this->_getRemoteList()->listPackages()
        );
    }

    public function testLatest()
    {
        $this->assertEquals(
            '1.0.0',
            $this->_getLatestRemote()->getLatestRelease('A')
        );
    }

    public function testLatestUri()
    {
        $this->assertEquals(
            'http://pear.horde.org/get/A-1.0.0.tgz',
            $this->_getLatestRemote()->getLatestDownloadUri('A')
        );
    }

    /**
     * @expectedException Horde_Pear_Exception
     */
    public function testLatestUriExceptionForNoRelease()
    {
        $this->_getLatestRemote()->getLatestDownloadUri('A', 'dev');
    }

    private function _getRemote()
    {
        return new Horde_Pear_Remote();
    }

    private function _getRemoteList()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $string = '<?xml version="1.0" encoding="UTF-8" ?>
<l><p xlink:href="/rest/p/a">A</p><p xlink:href="/rest/p/b">B</p></l>';
        $body = new Horde_Support_StringStream($string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $response->code = 200;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return $this->_createRemote($request);
    }

    private function _getLatestRemote()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $request = new Horde_Pear_Stub_Request();
        $request->setResponses(
            array(
                array(
                    'body' => '1.0.0',
                    'code' => 200,
                ),
                array(
                    'body' => '',
                    'code' => 404,
                ),
                array(
                    'body' => '',
                    'code' => 404,
                ),
                array(
                    'body' => '',
                    'code' => 404,
                ),
                array(
                    'body' => $this->_getRelease(),
                    'code' => 200,
                ),
            )
        );
        return $this->_createRemote($request);
    }

    private function _createRemote($request)
    {
        $access = new Horde_Pear_Rest_Access();
        $access->setRest(
            'http://test',
            new Horde_Pear_Rest(
                new Horde_Http_Client(array('request' => $request)),
                'http://test'
            )
        );
        return new Horde_Pear_Remote('test', $access);
    }
}
