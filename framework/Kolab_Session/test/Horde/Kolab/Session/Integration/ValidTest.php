<?php
/**
 * Test the valid check with the Kolab session handler implementation.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the valid check with the Kolab session handler implementation.
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
class Horde_Kolab_Session_Integration_ValidTest extends Horde_Kolab_Session_SessionTestCase
{
    public function setUp()
    {
        $this->user = $this->getMock(
            'Horde_Kolab_Server_Object_Hash', array(), array(), '', false, false
        );
    }

    public function testMethodIsvalidHasResultBooleanTrueIfTheSessionIsNotConnectedAndTheCurrentUserIsAnonymous()
    {
        $auth = $this->getMock('Horde_Interfaces_Registry_Auth');
        $auth->expects($this->once())
            ->method('getAuth')
            ->will($this->returnValue(''));
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertTrue($valid->isValid());
    }

    public function testMethodIsvalidHasResultBooleanFalseIfTheSessionIsNotConnected()
    {
        $auth = $this->getMock('Horde_Interfaces_Registry_Auth');
        $auth->expects($this->once())
            ->method('getAuth')
            ->will($this->returnValue('mail@example.org'));
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertFalse($valid->isValid());
    }

    public function testMethodIsvalidHasResultBooleanFalseIfTheMailOfTheCurrentUserDoesNotMatchTheCurrentUserOfTheSession()
    {
        $auth = $this->getMock('Horde_Interfaces_Registry_Auth');
        $auth->expects($this->once())
            ->method('getAuth')
            ->will($this->returnValue('somebody@example.org'));
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('mail@example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('', array('password' => ''));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertFalse($valid->isValid());
    }

    public function testMethodIsvalidHasResultBooleanTrueIfTheMailOfTheCurrentUserMatchesTheCurrentUserOfTheSessionAndNoNewUserWasSet()
    {
        $auth = $this->getMock('Horde_Interfaces_Registry_Auth');
        $auth->expects($this->once())
            ->method('getAuth')
            ->will($this->returnValue('mail@example.org'));
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('mail@example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('', array('password' => ''));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertTrue($valid->isValid());
    }

    public function testMethodIsvalidHasResultBooleanFalseIfTheMailOfTheCurrentUserMatchesTheCurrentUserOfTheSessionAndTheNewUserMatchesNeitherTheCurrentUserMailAndUid()
    {
        $auth = $this->getMock('Horde_Interfaces_Registry_Auth');
        $auth->expects($this->once())
            ->method('getAuth')
            ->will($this->returnValue('mail@example.org'));
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('mail@example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('', array('password' => ''));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertFalse($valid->isValid('somebody@example.org'));
    }

    public function testMethodIsvalidHasResultBooleanTrueIfTheMailOfTheCurrentUserMatchesTheCurrentUserOfTheSessionAndTheNewUserMatchesEitherTheCurrentUserMailAndUid()
    {
        $auth = $this->getMock('Horde_Interfaces_Registry_Auth');
        $auth->expects($this->once())
            ->method('getAuth')
            ->will($this->returnValue('mail@example.org'));
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('mail@example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('', array('password' => ''));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertTrue($valid->isValid('mail@example.org'));
    }
}