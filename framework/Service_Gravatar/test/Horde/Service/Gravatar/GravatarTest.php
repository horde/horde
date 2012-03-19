<?php
/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */

require_once __DIR__ . '/Autoload.php';

/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */
class Horde_Service_Gravatar_GravatarTest
extends PHPUnit_Framework_TestCase
{
    public function testReturn()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertInternalType('string', $g->getId('test'));
    }

    public function testAddress()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId('test@example.org')
        );
    }

    /**
     * @dataProvider provideAddresses
     */
    public function testAddresses($mail, $id)
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals($id, $g->getId($mail));
    }

    public function provideAddresses()
    {
        return array(
            array('test@example.org', '0c17bf66e649070167701d2d3cd71711'),
            array('x@example.org', 'ae46d8cbbb834a85db7287f8342d0c42'),
            array('test@example.com', '55502f40dc8b7c769880b10874abc9d0'),
        );
    }

    public function testIgnoreCase()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId('Test@EXAMPLE.orG')
        );
    }

    public function testTrimming()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId(' Test@Example.orG ')
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidMail()
    {
        $g = new Horde_Service_Gravatar();
        $g->getId(0.0);
    }

    public function testAvatarUrl()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            'http://www.gravatar.com/avatar/0c17bf66e649070167701d2d3cd71711',
            $g->getAvatarUrl(' Test@Example.orG ')
        );
    }

    public function testAvatarUrlWithSize()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            'http://www.gravatar.com/avatar/0c17bf66e649070167701d2d3cd71711?s=50',
            $g->getAvatarUrl('test@example.org', 50));
    }

    public function testProfileUrl()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            'http://www.gravatar.com/0c17bf66e649070167701d2d3cd71711',
            $g->getProfileUrl(' Test@Example.orG ')
        );
    }

    public function testFlexibleBase()
    {
        $g = new Horde_Service_Gravatar(Horde_Service_Gravatar::SECURE);
        $this->assertEquals(
            'https://secure.gravatar.com/0c17bf66e649070167701d2d3cd71711',
            $g->getProfileUrl(' Test@Example.orG ')
        );
    }

    public function testFetchProfile()
    {
        $g = $this->_getMockedGravatar('RESPONSE');
        $this->assertEquals(
            'RESPONSE',
            $g->fetchProfile('test@example.org')
        );
    }

    public function testGetProfile()
    {
        $g = $this->_getMockedGravatar('{"test":"example"}');
        $this->assertEquals(
            array('test' => 'example'),
            $g->getProfile('test@example.org')
        );
    }

    private function _getMockedGravatar($response_string)
    {
        $response = $this->getMock('Horde_Http_Response', array('getBody'));
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($response_string));

        $mock = $this->getMock('Horde_Http_Client', array('get'));
        $mock->expects($this->once())
            ->method('get')
            ->will($this->returnValue($response));

        return new Horde_Service_Gravatar(
            Horde_Service_Gravatar::STANDARD,
            $mock
        );
    }

    public function testFetchImage()
    {
        $g = $this->_getStubbedGravatar('RESPONSE');
        $this->assertEquals(
            'RESPONSE',
            stream_get_contents($g->fetchAvatar('test@example.org'))
        );
    }

    private function _getStubbedGravatar($response_string)
    {
        $body = new Horde_Support_StringStream($response_string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return new Horde_Service_Gravatar(
            Horde_Service_Gravatar::STANDARD,
            new Horde_Http_Client(array('request' => $request))
        );
    }
}