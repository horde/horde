<?php
/**
 * Test the mock request handler.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Controller
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Test the mock request handler.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Controller
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Controller_MockRequestTest extends Horde_Test_Case
{
    public function testEmptyGetPath()
    {
        $r = new Horde_Controller_Request_Mock();
        $this->assertNull($r->getPath());
    }

    public function testSetPath()
    {
        $r = new Horde_Controller_Request_Mock();
        $r->setPath('R');
        $this->assertEquals('R', $r->getPath());
    }

    public function testGetPathRedirectUrl()
    {
        $r = new Horde_Controller_Request_Mock(
            array('SERVER' => array('REDIRECT_URL' => 'RE'))
        );
        $this->assertEquals('RE', $r->getPath());
    }

    public function testGetPathRequestUri()
    {
        $r = new Horde_Controller_Request_Mock(
            array('SERVER' => array('REQUEST_URI' => 'RU'))
        );
        $this->assertEquals('RU', $r->getPath());
    }

    /**
     * @dataProvider provideGets
     */
    public function testGetGetVars($method, $key, $value)
    {
        $r = new Horde_Controller_Request_Mock(array($key => $value));
        $this->assertEquals($value, $r->{$method}());
    }

    public function provideGets()
    {
        return array(
            array('getGetVars', 'GET', array('X' => 'Y')),
            array('getFileVars', 'files', array('X' => 'Y')),
            array('getServerVars', 'server', array('X' => 'Y')),
            array('getPostVars', 'POST', array('X' => 'Y')),
            array('getCookieVars', 'cookie', array('X' => 'Y')),
            array('getRequestVars', 'REQUEST', array('X' => 'Y')),
        );
    }

    public function testGetHeaders()
    {
        $r = new Horde_Controller_Request_Mock(
            array('SERVER' => array('HTTP_TEST' => 'test'))
        );
        $this->assertEquals(array('test' => 'test'), $r->getHeaders());
    }

    public function testGetHeaderNames()
    {
        $r = new Horde_Controller_Request_Mock(
            array('SERVER' => array('HTTP_TEST' => 'test'))
        );
        $this->assertEquals(array('test'), $r->getHeaderNames());
    }
}
