<?php
/**
 * Test the Kolab session handler base implementation.
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
 * Test the Kolab session handler base implementation.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Class_BaseTest extends Horde_Kolab_Session_SessionTestCase
{
    public function testMethodConstructHasParameterStringUserid()
    {
        $session = new Horde_Kolab_Session_Base(
            'userid', $this->_getComposite(), array()
        );
    }

    public function testMethodConstructHasParameterServercompositeServer()
    {
        $session = new Horde_Kolab_Session_Base(
            '', $this->_getComposite(), array()
        );
    }

    public function testMethodConstructHasParameterArrayParams()
    {
        $session = new Horde_Kolab_Session_Base(
            '', $this->_getComposite(), array('params' => 'params')
        );
    }

    public function testMethodConnectHasParameterArrayCredentials()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            '', $composite, array()
        );
        $session->connect(array('password' => ''));
    }

    public function testMethodConnectHasPostconditionThatTheUserMailAddressIsKnown()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->returnValue('mail@example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            '', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('mail@example.org', $session->getMail());
    }

    public function testMethodConnectHasPostconditionThatTheUserUidIsKnown()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->returnValue('uid'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            '', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('uid', $session->getUid());
    }

    public function testMethodConnectHasPostconditionThatTheUserNameIsKnown()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->returnValue('name'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            '', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('name', $session->getName());
    }

    public function testMethodConnectHasPostconditionThatTheUsersImapHostIsKnown()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->returnValue('home.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('home.example.org', $session->getImapServer());
    }

    public function testMethodConnectHasPostconditionThatTheUsersFreebusyHostIsKnown()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->returnValue('freebusy.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite,
            array('freebusy' => array('url_format' => 'https://%s/fb'))
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('https://freebusy.example.org/fb', $session->getFreebusyServer());
    }

    public function testMethodConnectThrowsExceptionIfTheCredentialsHaveNoPasswordEntry()
    {
        $session = new Horde_Kolab_Session_Base(
            'user', $this->_getComposite(), array()
        );
        try {
            $session->connect(array());
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->assertEquals('Missing password!', $e->getMessage());
        }
    }

    public function testMethodConnectThrowsExceptionIfTheConnectionFailed()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $composite = $this->_getMockedComposite();
        $composite->server->expects($this->exactly(1))
            ->method('connectGuid')
            ->will($this->throwException(new Horde_Kolab_Server_Exception('Error')));
        $session = new Horde_Kolab_Session_Base(
            'user', $composite, array()
        );
        try {
            $session->connect(array('password' => 'pass'));
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->assertEquals('Error', $e->getMessage());
        }
    }

    public function testMethodSleepHasResultArrayThePropertiesToSerialize()
    {
        $session = new Horde_Kolab_Session_Base(
            'user', $this->_getComposite(), array()
        );
        $this->assertEquals(
            array(
                '_params',
                '_user_id',
                '_user_uid',
                '_user_mail',
                '_user_name',
                '_imap_server',
                '_freebusy_server',
                '_storage_params',
            ), $session->__sleep()
        );
    }

    public function testMethodGetidHasResultStringTheIdOfTheUserUserUsedForConnecting()
    {
        $session = new Horde_Kolab_Session_Base(
            'userid', $this->_getComposite(), array()
        );
        $this->assertEquals('userid', $session->getId());
    }

    public function testMethodGetmailHasResultStringTheMailOfTheConnectedUser()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('userid', $session->getMail());
    }

    public function testMethodGetuidHasResultStringTheUidOfTheConnectedUser()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('userid', $session->getUid());
    }

    public function testMethodGetnameHasResultStringTheNameOfTheConnectedUser()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('userid', $session->getName());
    }

    public function testMethodGetfreebusyserverHasResultStringTheUsersFreebusyServerConverterdToACompleteUrlUsingParametersIfAvailable()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->returnValue('freebusy.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite,
            array('freebusy' => array('url_format' => 'https://%s/fb'))
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('https://freebusy.example.org/fb', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringTheUsersFreebusyServerConverterdToACompleteUrlUsingFreebusyIfAvailable()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->returnValue('freebusy.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('http://freebusy.example.org/freebusy', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringTheConfiguredServerIfAvailable()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite,
            array('freebusy' => array('url' => 'https://freebusy2.example.org/fb'))
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('https://freebusy2.example.org/fb', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringTheUsersHomeServerConverterdToACompleteUrlUsingParametersIfAvailable()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite,
            array('freebusy' => array('url_format' => 'https://%s/fb'))
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('https://localhost/fb', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringTheUsersHomeServerConverterdToACompleteUrlUsingFreebusyIfAvailable()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('http://localhost/freebusy', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringLocalhostConvertedToACompleteUrlUsingParametersIfAvailable()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite,
            array('freebusy' => array('url_format' => 'https://%s/fb'))
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('https://localhost/fb', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringLocalhostConvertedToACompleteUrlUsingFreebusy()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('http://localhost/freebusy', $session->getFreebusyServer());
    }

    public function testMethodGetimapserverHasResultStringTheUsersHomeServerIfAvailable()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->returnValue('home.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('home.example.org', $session->getImapServer());
    }

    public function testMethodGetimapserverHasResultStringTheConfiguredServerIfAvailable()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite,
            array('imap' => array('server' => 'imap.example.org'))
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('imap.example.org', $session->getImapServer());
    }

    public function testMethodGetimapserverHasResultStringLocalhostIfNoAlternative()
    {
        $user = $this->getMock('Horde_Kolab_Server_Object');
        $user->expects($this->exactly(5))
            ->method('getExternal')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        $session = new Horde_Kolab_Session_Base(
            'userid', $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('localhost', $session->getImapServer());
    }

    public function testMethodGetstorageHasResultKolabstorageConnectionForTheCurrentUser()
    {
        $this->markTestIncomplete('Not implemented');
    }
}