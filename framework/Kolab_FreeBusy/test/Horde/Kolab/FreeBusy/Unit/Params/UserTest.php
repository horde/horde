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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test retrieving the user parameter.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_FreeBusy_Unit_Params_UserTest
extends PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $param = new Horde_Kolab_FreeBusy_Params_User(
            array('PHP_AUTH_USER' => 'test')
        );
        $this->assertEquals('test', $param->getId());
    }

    public function testGetCredentials()
    {
        $param = new Horde_Kolab_FreeBusy_Params_User(
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'pw'
            )
        );
        $this->assertEquals(array('test', 'pw'), $param->getCredentials());
    }

    public function testEmpty()
    {
        $param = new Horde_Kolab_FreeBusy_Params_User();
        $this->assertEquals('', $param->getId());
    }

    public function testCredentials()
    {
        $param = new Horde_Kolab_FreeBusy_Params_User();
        $this->assertEquals(array('', null), $param->getCredentials());
    }

    public function testCgi()
    {
        $param = new Horde_Kolab_FreeBusy_Params_User(
            array(
                'REDIRECT_REDIRECT_REMOTE_USER' => '123456' . base64_encode('test:TEST')
            )
        );
        $this->assertEquals(array('test', 'TEST'), $param->getCredentials());
    }

}