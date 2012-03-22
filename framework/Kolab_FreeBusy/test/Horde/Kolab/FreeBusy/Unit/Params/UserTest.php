<?php
/**
 * Test retrieving the user parameter.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test retrieving the user parameter.
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
class Horde_Kolab_FreeBusy_Unit_Params_UserTest
extends PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $param = $this->_getUserParam(array('PHP_AUTH_USER' => 'test'));
        $this->assertEquals('test', $param->getUser());
    }

    public function testGetCredentials()
    {
        $param = $this->_getUserParam(
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'pw'
            )
        );
        $this->assertEquals(array('test', 'pw'), $param->getCredentials());
    }

    public function testEmpty()
    {
        $param = $this->_getUserParam(array());
        $this->assertEquals('', $param->getUser());
    }

    public function testCredentials()
    {
        $param = $this->_getUserParam(array());
        $this->assertEquals(array('', null), $param->getCredentials());
    }

    public function testCgi()
    {
        $param = $this->_getUserParam(
            array(
                'REDIRECT_REDIRECT_REMOTE_USER' => '123456' . base64_encode('test:TEST')
            )
        );
        $this->assertEquals(array('test', 'TEST'), $param->getCredentials());
    }

    private function _getUserParam($vars)
    {
        return new Horde_Kolab_FreeBusy_Params_User(
            new Horde_Controller_Request_Mock(
                array('server' => $vars)
            )
        );

    }
}