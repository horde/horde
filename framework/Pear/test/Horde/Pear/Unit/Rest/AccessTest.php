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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the rest access helper.
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
class Horde_Pear_Unit_Rest_AccessTest
extends Horde_Pear_TestCase
{
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
