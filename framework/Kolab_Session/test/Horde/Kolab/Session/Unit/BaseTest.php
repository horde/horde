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
class Horde_Kolab_Session_Unit_BaseTest extends Horde_Kolab_Session_TestCase
{
    public function setUp()
    {
        $this->user = $this->getMock(
            'Horde_Kolab_Server_Object_Hash', array(), array(), '', false, false
        );
    }

    public function testMethodConstructHasParameterServercompositeServer()
    {
        $session = new Horde_Kolab_Session_Base(
            $this->_getComposite(), array()
        );
    }

    public function testMethodConstructHasParameterArrayParams()
    {
        $session = new Horde_Kolab_Session_Base(
            $this->_getComposite(), array('params' => 'params')
        );
    }

    public function testMethodConnectHasParameterStringUserid()
    {
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
        $session->connect('userid', array('password' => ''));
    }

    public function testMethodConnectHasParameterArrayCredentials()
    {
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
    }

    public function testMethodConnectHasPostconditionThatTheUserMailAddressIsKnown()
    {
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
        $session->connect(array('password' => ''));
        $this->assertEquals('mail@example.org', $session->getMail());
    }

    public function testMethodConnectHasPostconditionThatTheUserUidIsKnown()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('uid'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('uid', $session->getUid());
    }

    public function testMethodConnectHasPostconditionThatTheUserNameIsKnown()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('name'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect(array('password' => ''));
        $this->assertEquals('name', $session->getName());
    }

    public function testMethodConnectHasPostconditionThatTheUsersImapHostIsKnown()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('home.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('home.example.org', $session->getImapServer());
    }

    public function testMethodConnectHasPostconditionThatTheUsersFreebusyHostIsKnown()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('freebusy.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite,
            array('freebusy' => array('url_format' => 'https://%s/fb'))
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('https://freebusy.example.org/fb', $session->getFreebusyServer());
    }

    public function testMethodConnectThrowsExceptionIfTheConnectionFailed()
    {
        $composite = $this->_getMockedComposite();
        $composite->server->expects($this->exactly(1))
            ->method('connectGuid')
            ->will($this->throwException(new Horde_Kolab_Server_Exception('Error')));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        try {
            $session->connect('user', array('password' => 'pass'));
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->assertEquals('Login failed!', $e->getMessage());
        }
    }

    public function testMethodConnectThrowsExceptionIfTheCredentialsWereInvalid()
    {
        $composite = $this->_getMockedComposite();
        $composite->server->expects($this->exactly(1))
            ->method('connectGuid')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Bindfailed('Error')));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        try {
            $session->connect('user', array('password' => 'pass'));
        } catch (Horde_Kolab_Session_Exception_Badlogin $e) {
            $this->assertEquals('Invalid credentials!', $e->getMessage());
        }
    }

    public function testMethodGetidHasResultStringTheIdOfTheUserUserUsedForConnecting()
    {
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
        $session->connect('userid', array('password' => 'pass'));
        $this->assertEquals('userid', $session->getId());
    }

    public function testMethodGetmailHasResultStringTheMailOfTheConnectedUser()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('userid', $session->getMail());
    }

    public function testMethodGetuidHasResultStringTheUidOfTheConnectedUser()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('userid', $session->getUid());
    }

    public function testMethodGetnameHasResultStringTheNameOfTheConnectedUser()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('userid', $session->getName());
    }

    public function testMethodGetfreebusyserverHasResultStringTheUsersFreebusyServerConverterdToACompleteUrlUsingParametersIfAvailable()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('freebusy.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite,
            array('freebusy' => array('url_format' => 'https://%s/fb'))
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('https://freebusy.example.org/fb', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringTheUsersFreebusyServerConverterdToACompleteUrlUsingFreebusyIfAvailable()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('freebusy.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('http://freebusy.example.org/freebusy', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringTheConfiguredServerIfAvailable()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite,
            array('freebusy' => array('url' => 'https://freebusy2.example.org/fb'))
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('https://freebusy2.example.org/fb', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringTheUsersHomeServerConverterdToACompleteUrlUsingParametersIfAvailable()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite,
            array('freebusy' => array('url_format' => 'https://%s/fb'))
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('https://localhost/fb', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringTheUsersHomeServerConverterdToACompleteUrlUsingFreebusyIfAvailable()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('http://localhost/freebusy', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringLocalhostConvertedToACompleteUrlUsingParametersIfAvailable()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite,
            array('freebusy' => array('url_format' => 'https://%s/fb'))
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('https://localhost/fb', $session->getFreebusyServer());
    }

    public function testMethodGetfreebusyserverHasResultStringLocalhostConvertedToACompleteUrlUsingFreebusy()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('http://localhost/freebusy', $session->getFreebusyServer());
    }

    public function testMethodGetimapserverHasResultStringTheUsersHomeServerIfAvailable()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->returnValue('home.example.org'));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('home.example.org', $session->getImapServer());
    }

    public function testMethodGetimapserverHasResultStringTheConfiguredServerIfAvailable()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite,
            array('imap' => array('server' => 'imap.example.org'))
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('imap.example.org', $session->getImapServer());
    }

    public function testMethodGetimapserverHasResultStringLocalhostIfNoAlternative()
    {
        $this->user->expects($this->exactly(5))
            ->method('getSingle')
            ->will($this->throwException(new Horde_Kolab_Server_Exception_Novalue()));
        $composite = $this->_getMockedComposite();
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->user));
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->connect('userid', array('password' => ''));
        $this->assertEquals('localhost', $session->getImapServer());
    }

    public function testEmptyGetId()
    {
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $this->assertNull($session->getId());
    }

    public function testEmptyGetMail()
    {
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $this->assertNull($session->getMail());
    }

    public function testEmptyGetName()
    {
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $this->assertNull($session->getName());
    }

    public function testEmptyGetUid()
    {
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $this->assertNull($session->getUid());
    }

    public function testEmptyGetFreebusyServer()
    {
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $this->assertNull($session->getFreebusyServer());
    }

    public function testEmptyGetImapServer()
    {
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $this->assertNull($session->getImapServer());
    }

    public function testImportExport()
    {
        $data = array('test');
        $composite = $this->_getMockedComposite();
        $session = new Horde_Kolab_Session_Base(
            $composite, array()
        );
        $session->import($data);
        $this->assertEquals($data, $session->export());
    }

}