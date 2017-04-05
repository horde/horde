<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */

/**
 * Test the rest access helper.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @copyright  2011-2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */
class Horde_Pear_Unit_Rest_AccessTest
extends Horde_Pear_TestCase
{
    public function testLatestRelease()
    {
        $this->assertEquals(
            '1.0.0',
            $this->_getRest()->fetchLatestRelease('A')
        );
        $this->assertEquals(
            '1.0.0',
            $this->_getRest()->fetchLatestPackageReleases('A')['stable']
        );
    }

    public function testDownloadUri()
    {
        $release = new Horde_Pear_Rest_Release(
            $this->_getReleaseRest()->fetchReleaseInformation('A', '1.0.0')
        );
        $this->assertEquals(
            'http://pear.horde.org/get/A-1.0.0.tgz',
            $release->getDownloadUri()
        );
    }

    public function testPackageXmlName()
    {
        $package = new Horde_Pear_Rest_Package(
            $this->_getPackageInfoRest()->fetchPackageInformation('A')
        );
        $this->assertEquals('Horde_Core', $package->getName());
    }

    public function testReleaseExists()
    {
        $this->assertTrue(
            $this->_getPackageRest()->releaseExists('A', '1.0.0')
        );
    }

    public function testReleaseDoesNotExist()
    {
        $this->assertFalse(
            $this->_getPackageRest(404)->releaseExists('A', '1.0.0')
        );
    }

    public function testChannelXml()
    {
        $this->assertEquals(
            'b:1;',
            $this->_getDependencyRest()->fetchChannelXml()
        );
    }

    private function _getRest()
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
            )
        );
        return $this->_createRest($request);
    }

    private function _getReleaseRest()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $string = $this->_getRelease();
        $body = new Horde_Support_StringStream($string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $response->code = 200;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return $this->_createRest($request);
    }

    private function _getDependencyRest()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $string = 'b:1;';
        $body = new Horde_Support_StringStream($string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $response->code = 200;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return $this->_createRest($request);
    }

    private function _getPackageRest($code = 200)
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $response = new Horde_Http_Response_Mock(
            '',
            fopen(__DIR__ . '/../../fixture/horde/horde/package.xml', 'r')
        );
        $response->code = $code;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return $this->_createRest($request);
    }

    private function _getPackageInfoRest()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $response = new Horde_Http_Response_Mock(
            '',
            fopen(__DIR__ . '/../../fixture/rest/package.xml', 'r')
        );
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return $this->_createRest($request);
    }

    private function _createRest($request)
    {
        return new Horde_Pear_Rest(
            new Horde_Http_Client(array('request' => $request)),
            'http://test'
        );
    }
}
