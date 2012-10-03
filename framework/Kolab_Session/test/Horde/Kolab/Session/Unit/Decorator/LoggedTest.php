<?php
/**
 * Test the log decorator.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the log decorator.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
                'debug',
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

    public function testExport()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('export')
            ->will($this->returnValue('test'));
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('somebody@example.org'));
        $this->logger->expects($this->once())
            ->method('__call')
            ->with(
                'debug',
                array(
                    'Exported session data for "somebody@example.org" (s:4:"test";).'
                )
            );
        $logged = new Horde_Kolab_Session_Decorator_Logged(
            $session, $this->logger
        );
        $logged->export();
    }

    public function testImport()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('import')
            ->with(array('test'));
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('somebody@example.org'));
        $this->logger->expects($this->once())
            ->method('__call')
            ->with(
                'debug',
                array(
                    'Imported session data for "somebody@example.org" (a:1:{i:0;s:4:"test";}).'
                )
            );
        $logged = new Horde_Kolab_Session_Decorator_Logged(
            $session, $this->logger
        );
        $logged->import(array('test'));
    }

    public function testPurge()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('purge');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('somebody@example.org'));
        $this->logger->expects($this->once())
            ->method('__call')
            ->with(
                'warn',
                array(
                    'Purging session data for "somebody@example.org".'
                )
            );
        $logged = new Horde_Kolab_Session_Decorator_Logged(
            $session, $this->logger
        );
        $logged->purge();
    }
}