<?php
/**
 * Test the anonymous decorator.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the anonymous decorator.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Unit_Decorator_AnonymousTest
extends Horde_Kolab_Session_TestCase
{
    public function testMethodConnectHasPostconditionThatTheConnectionHasBeenEstablishedAsAnonymousUserIfRequired()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('connect')
            ->with('anonymous', array('password' => 'pass'));
        $anonymous = new Horde_Kolab_Session_Decorator_Anonymous(
            $session, 'anonymous', 'pass'
        );
        $anonymous->connect();
    }

    public function testMethodGetidReturnsNullIfConnectedUserIsAnonymousUser()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('anonymous'));
        $anonymous = new Horde_Kolab_Session_Decorator_Anonymous(
            $session, 'anonymous', 'pass'
        );
        $this->assertNull($anonymous->getId());
    }

    public function testMethodConnectGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('connect')
            ->with(array('password' => 'pass'));
        $anonymous = new Horde_Kolab_Session_Decorator_Anonymous(
            $session, 'anonymous', 'pass'
        );
        $anonymous->connect(array('password' => 'pass'));
    }

    public function testMethodGetidGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('1'));
        $anonymous = new Horde_Kolab_Session_Decorator_Anonymous(
            $session, 'anonymous', 'pass'
        );
        $anonymous->getId();
    }
}