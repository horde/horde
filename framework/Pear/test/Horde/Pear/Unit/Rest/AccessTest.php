<?php
/**
 * Test the rest access helper.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the rest access helper.
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
class Horde_Pear_Unit_Rest_AccessTest
extends Horde_Pear_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete();
    }


    public function testLatestRelease()
    {
        $this->assertEquals(
            '1.0.0',
            $this->_getAccess()->getLatestRelease('A', 'stable')
        );
    }

    public function testGetRelease()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Rest_Release',
            $this->_getReleaseAccess()->getRelease('A', '1.0.0')
        );
    }

    public function testDownloadUri()
    {
        $this->assertEquals(
            'http://pear.horde.org/get/A-1.0.0.tgz',
            $this->_getReleaseAccess()
            ->getRelease('A', '1.0.0')
            ->getDownloadUri()
        );
    }

    public function testDependencies()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Rest_Dependencies',
            $this->_getDependencyAccess()->getDependencies('A', '1.0.0')
        );
    }

    public function testPackageXml()
    {
        $this->assertInstanceOf(
            'Horde_Pear_Package_Xml',
            $this->_getPackageAccess()->getPackageXml('A', '1.0.0')
        );
    }

    public function testPackageXmlName()
    {
        $this->assertEquals(
            'Fixture',
            $this->_getPackageAccess()->getPackageXml('A', '1.0.0')->getName()
        );
    }

    public function testReleaseExists()
    {
        $this->assertTrue(
            $this->_getPackageAccess()->releaseExists('A', '1.0.0')
        );
    }

    public function testReleaseDoesNotExist()
    {
        $this->assertFalse(
            $this->_getPackageAccess(404)->releaseExists('A', '1.0.0')
        );
    }

    public function testChannelXml()
    {
        $this->assertEquals(
            'b:1;',
            $this->_getDependencyAccess()->getChannel()
        );
    }

    private function _getAccess()
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
        return $this->_createAccess($request);
    }

    private function _getReleaseAccess()
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
        return $this->_createAccess($request);
    }

    private function _getDependencyAccess()
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
        return $this->_createAccess($request);
    }

    private function _getPackageAccess($code = 200)
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
        return $this->_createAccess($request);
    }

    private function _createAccess($request)
    {
        $access = new Horde_Pear_Rest_Access();
        $access->setServer('test');
        $access->setRest(
            'http://test',
            new Horde_Pear_Rest(
                new Horde_Http_Client(array('request' => $request)),
                'http://test'
            )
        );
        return $access;
    }
}
