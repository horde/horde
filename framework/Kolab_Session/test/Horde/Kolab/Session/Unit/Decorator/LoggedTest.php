<?php
/**
 * Test the log decorator.
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
 * Test the log decorator.
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
class Horde_Kolab_Session_Unit_Decorator_LoggedTest
extends Horde_Kolab_Session_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setupLogger();
    }

    public function testMethodConnectHasPostconditionThatASuccessfulConnectionGetsLogged()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('connect')
            ->with(array('password' => 'pass'));
        $session->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('somebody@example.org'));
        $this->logger->expects($this->once())
            ->method('__call')
            ->with(
                'info',
                array(
                    'Connected Kolab session for "somebody@example.org".'
                )
            );
        $logged = new Horde_Kolab_Session_Decorator_Logged(
            $session, $this->logger
        );
        $logged->connect(array('password' => 'pass'));
    }

    public function testMethodConnectHasPostconditionThatAnUnsuccessfulConnectionGetsLogged()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('connect')
            ->will($this->throwException(new Horde_Kolab_Session_Exception('Error.')));
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('somebody@example.org'));
        $this->logger->expects($this->once())
            ->method('__call')
            ->with(
                'err',
                array(
                    'Failed to connect Kolab session for "somebody@example.org". Error was: Error.'
                )
            );
        $logged = new Horde_Kolab_Session_Decorator_Logged(
            $session, $this->logger
        );
        try {
            $logged->connect(array('password' => 'pass'));
            $this->fail('No Exception!');
        } catch (Horde_Kolab_Session_Exception $e) {
        }
    }
}