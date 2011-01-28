<?php
/**
 * Test the Horde_Auth_Kolab:: class.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Horde_Auth_Kolab:: class.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */
class Horde_Auth_Unit_KolabTest extends Horde_Auth_TestCase
{
    public function setUp()
    {
        if (!interface_exists('Horde_Kolab_Session')) {
            $this->markTestSkipped('The Kolab_Session package is apparently not installed (Interface Horde_Kolab_Session is unavailable).');
        }
        $this->kolab = $this->getMock('Horde_Kolab_Session');
        $this->driver = new Horde_Auth_Kolab(array('kolab' => $this->kolab));
    }

    public function testAuthenticate()
    {
        $this->kolab->expects($this->once())
            ->method('connect')
            ->with('user', array('password' => 'password'))
            ->will($this->returnValue(null));
        $this->assertTrue($this->driver->authenticate('user', array('password' => 'password')));
    }

    public function testBadLogin()
    {
        $this->kolab->expects($this->once())
            ->method('connect')
            ->with('user', array('password' => 'incorrect'))
            ->will($this->throwException(new Horde_Kolab_Session_Exception_Badlogin()));
        try {
            $this->driver->authenticate('user', array('password' => 'incorrect'));
        } catch (Horde_Auth_Exception $e) {
            $this->assertEquals(Horde_Auth::REASON_BADLOGIN, $e->getCode());
        }
    }

    public function testFailure()
    {
        $this->kolab->expects($this->once())
            ->method('connect')
            ->with('user', array('password' => ''))
            ->will($this->throwException(new Horde_Kolab_Session_Exception()));
        try {
            $this->driver->authenticate('user', array('password' => ''));
        } catch (Horde_Auth_Exception $e) {
            $this->assertEquals(Horde_Auth::REASON_FAILED, $e->getCode());
        }
    }

    public function testUidRewrite()
    {
        $this->kolab->expects($this->once())
            ->method('connect')
            ->with('user', array('password' => 'password'))
            ->will($this->returnValue(null));
        $this->kolab->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('user@example.com'));
        $this->driver->authenticate('user', array('password' => 'password'));
        $this->assertEquals(
            'user@example.com', $this->driver->getCredential('userId')
        );
    }
}