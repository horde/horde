<?php
/**
 * Test the logging decorator for the user.
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
 * Test the logging decorator for the user.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_FreeBusy_Unit_User_Decorator_LogTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testGetPrimaryId()
    {
        $this->assertEquals('', $this->_getUser()->getPrimaryId());
    }

    public function testGetDomain()
    {
        $this->assertEquals('', $this->_getUser()->getDomain());
    }

    public function testGetGroups()
    {
        $this->assertEquals(array(), $this->_getUser()->getGroups());
    }

    public function testIsAuthenticated()
    {
        $this->assertFalse($this->_getUser()->isAuthenticated());
    }

    public function testAuthLogAnonymous()
    {
        $this->_getUser()->isAuthenticated();
        $this->assertLogContains('Anonymous access from "test" to free/busy.');
    }

    public function testAuthLog()
    {
        $this->_getAuthUser()->isAuthenticated();
        $this->assertLogContains('Login success for "mail@example.org" from "test" to free/busy.');
    }

    private function _getUser()
    {
        $request = new Horde_Controller_Request_Mock(
            array('server' => array('REMOTE_ADDR' => 'test'))
        );
        return new Horde_Kolab_FreeBusy_User_Decorator_Log(
            new Horde_Kolab_FreeBusy_User_Anonymous(),
            $request,
            $this->getMockLogger()
        );
    }

    private function _getAuthUser()
    {
        $request = new Horde_Controller_Request_Mock(
            array('server' => array('REMOTE_ADDR' => 'test'))
        );
        return new Horde_Kolab_FreeBusy_User_Decorator_Log(
            $this->getAuthUser(),
            $request,
            $this->getMockLogger()
        );
    }
}