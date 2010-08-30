<?php
/**
 * Test the log decorator for validators.
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
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the log decorator for validators.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Class_Valid_Decorator_LoggedTest
extends Horde_Kolab_Session_SessionTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setupLogger();
    }

    public function testMethodIsvalidHasPostconditionThatAnInvalidSessionGetsLogged()
    {
        $auth = $this->getMock('Horde_Interfaces_Registry_Auth');
        $auth->expects($this->exactly(2))
            ->method('getAuth')
            ->will($this->returnValue('auth@example.org'));
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->exactly(2))
            ->method('getMail')
            ->will($this->returnValue('somebody@example.org'));
        $this->logger->expects($this->once())
            ->method('__call')
            ->with(
                'info',
                array('Invalid Kolab session for current user "auth@example.org", requested user "nobody@example.org" and stored user "somebody@example.org".')
            );
        $logged = new Horde_Kolab_Session_Decorator_Logged(
            $session, $this->logger
        );
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $logged = new Horde_Kolab_Session_Valid_Decorator_Logged(
            $valid, $this->logger
        );
        $this->assertFalse($logged->isValid('nobody@example.org'));
    }

    public function testMethodIsvalidGetsDelegated()
    {
        $valid = $this->getMock('Horde_Kolab_Session_Valid_Interface');
        $valid->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $logged = new Horde_Kolab_Session_Valid_Decorator_Logged(
            $valid, $this->logger
        );
        $this->assertTrue($logged->isValid());
    }

    public function testMethodGetsessionGetsDelegated()
    {
        $valid = $this->getMock('Horde_Kolab_Session_Valid_Interface');
        $valid->expects($this->once())
            ->method('getSession');
        $logged = new Horde_Kolab_Session_Valid_Decorator_Logged($valid, $this->logger);
        $logged->getSession();
    }

    public function testMethodGetauthGetsDelegated()
    {
        $valid = $this->getMock('Horde_Kolab_Session_Valid_Interface');
        $valid->expects($this->once())
            ->method('getAuth');
        $logged = new Horde_Kolab_Session_Valid_Decorator_Logged($valid, $this->logger);
        $logged->getAuth();
    }
}