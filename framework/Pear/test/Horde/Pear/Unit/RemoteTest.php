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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the package contents.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_RemoteTest
extends Horde_Pear_TestCase
{
    public function testListPackages()
    {
        $this->assertInternalType(
            'array',
            $this->getRemoteList()->listPackages()
        );
    }

    public function testListPackagesContainsComponents()
    {
        $this->assertEquals(
            array('A', 'B'),
            $this->getRemoteList()->listPackages()
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

    public function testNoDetails()
    {
        $this->assertFalse(
            $this->_getNoLatest()->getLatestDetails('X', null)
        );
    }

    public function testLatestDetails()
    {
        $this->assertEquals(
            '1.0.0',
            $this->_getLatest()->getLatestDetails('A', null)->getVersion()
        );
    }

    public function testDependencies()
    {
        $this->assertEquals(
            array(array('name' => 'test', 'type' => 'pkg', 'optional' => 'no')),
            $this->_getRemoteDependencies()->getDependencies('A', '1.0.0')
        );
    }

    public function testChannel()
    {
        $this->assertEquals(
            'a:1:{s:8:"required";a:1:{s:7:"package";a:1:{s:4:"name";s:4:"test";}}}',
            $this->_getRemoteDependencies()->getChannel()
        );
    }

    public function testPackageXml()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Xml',
            $this->_getPackageXml()->getPackageXml('A', null)
        );
    }


    private function _getRemoteDependencies()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $string = serialize(array('required' => array('package' => array('name' => 'test'))));
        $body = new Horde_Support_StringStream($string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $response->code = 200;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return $this->createRemote($request);
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
        return $this->createRemote($request);
    }

    private function _getNoLatest()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $request = new Horde_Pear_Stub_Request();
        $request->setResponses(
            array(
                array(
                    'body' => '',
                    'code' => 404,
                ),
            )
        );
        return $this->createRemote($request);
    }

    private function _getLatest()
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
                    'body' => $this->_getRelease(),
                    'code' => 200,
                ),
            )
        );
        return $this->createRemote($request);
    }

    private function _getPackageXml()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $request = new Horde_Pear_Stub_Request();
        $request->setResponses(
            array(
                array(
                    'body' => file_get_contents(
                        __DIR__ . '/../fixture/rest/package.xml'
                    ),
                    'code' => 404,
                ),
            )
        );
        return $this->createRemote($request);
    }
}
