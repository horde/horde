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
class Horde_Kolab_Session_Unit_Valid_Decorator_LoggedTest
extends Horde_Kolab_Session_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setupLogger();
    }

    public function testMethodValidateHasPostconditionThatAnInvalidSessionGetsLogged()
    {
        $auth = 'auth@example.org';
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->exactly(2))
            ->method('getMail')
            ->will($this->returnValue('somebody@example.org'));
        $this->logger->expects($this->exactly(2))
            ->method('__call')
            ->with(
                'info',
                $this->logicalOr(
                    array('Invalid Kolab session for current user "auth@example.org" and requested user "nobody@example.org".'),
                    array('Validating Kolab session for current user "auth@example.org", requested user "nobody@example.org", and stored user "somebody@example.org".')
                )
            );
        $logged = new Horde_Kolab_Session_Decorator_Logged(
            $session, $this->logger
        );
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $logged = new Horde_Kolab_Session_Valid_Decorator_Logged(
            $valid, $this->logger
        );
        $this->assertFalse($logged->validate('nobody@example.org'));
    }

    public function testMethodValidateGetsDelegated()
    {
        $valid = $this->getMock('Horde_Kolab_Session_Valid');
        $valid->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(true));
        $valid->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->getMock('Horde_Kolab_Session')));
        $logged = new Horde_Kolab_Session_Valid_Decorator_Logged(
            $valid, $this->logger
        );
        $this->assertTrue($logged->validate());
    }

    public function testMethodGetsessionGetsDelegated()
    {
        $valid = $this->getMock('Horde_Kolab_Session_Valid');
        $valid->expects($this->once())
            ->method('getSession');
        $logged = new Horde_Kolab_Session_Valid_Decorator_Logged($valid, $this->logger);
        $logged->getSession();
    }

    public function testMethodGetauthGetsDelegated()
    {
        $valid = $this->getMock('Horde_Kolab_Session_Valid');
        $valid->expects($this->once())
            ->method('getAuth');
        $logged = new Horde_Kolab_Session_Valid_Decorator_Logged($valid, $this->logger);
        $logged->getAuth();
    }
}