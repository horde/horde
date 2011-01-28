<?php
/**
 * Test the valid check.
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
 * Test the valid check.
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
class Horde_Kolab_Session_Unit_Valid_BaseTest extends Horde_Kolab_Session_TestCase
{
    public function testMethodIsvalidHasResultBooleanTrueIfTheSessionIsNotConnectedAndTheCurrentUserIsAnonymous()
    {
        $auth = false;
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue(''));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertTrue($valid->validate());
    }

    public function testMethodIsvalidHasResultBooleanFalseIfTheSessionIsNotConnected()
    {
        $auth = 'mail@example.org';
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue(''));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertFalse($valid->validate());
    }

    public function testMethodIsvalidHasResultBooleanFalseIfTheMailOfTheCurrentUserDoesNotMatchTheCurrentUserOfTheSession()
    {
        $auth = 'somebody@example.org';
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('mail@example.org'));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertFalse($valid->validate());
    }

    public function testMethodIsvalidHasResultBooleanTrueIfTheMailOfTheCurrentUserMatchesTheCurrentUserOfTheSessionAndNoNewUserWasSet()
    {
        $auth = 'mail@example.org';
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('mail@example.org'));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertTrue($valid->validate());
    }

    public function testMethodIsvalidHasResultBooleanFalseIfTheMailOfTheCurrentUserMatchesTheCurrentUserOfTheSessionAndTheNewUserMatchesNeitherTheCurrentUserMailAndUid()
    {
        $auth = 'mail@example.org';
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('mail@example.org'));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertFalse($valid->validate('somebody@example.org'));
    }

    public function testMethodIsvalidHasResultBooleanTrueIfTheMailOfTheCurrentUserMatchesTheCurrentUserOfTheSessionAndTheNewUserMatchesEitherTheCurrentUserMailAndUid()
    {
        $auth = 'mail@example.org';
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('mail@example.org'));
        $valid = new Horde_Kolab_Session_Valid_Base($session, $auth);
        $this->assertTrue($valid->validate('mail@example.org'));
    }
}