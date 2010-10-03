<?php
/**
 * Test the Kolab_Session factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Kolab_Session factory.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_KolabSessionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Needs some love');
    }

    private function _getFactory()
    {
        $GLOBALS['conf']['kolab']['server']['basedn'] = 'test';
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $server_factory = new Horde_Core_Factory_KolabServer($injector);
        $factory = new Horde_Core_Factory_KolabSession($injector);
        $this->session_auth = $this->getMock('Horde_Kolab_Session_Auth_Interface');
        $this->session_storage = $this->getMock('Horde_Kolab_Session_Storage_Interface');
        $injector->setInstance('Horde_Kolab_Session_Auth_Interface', $this->session_auth);
        $injector->setInstance('Horde_Kolab_Session_Storage_Interface', $this->session_storage);
        return $factory;
    }

    public function testMethodGetvalidatorHasResultHordekolabsessionvalid()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $this->assertType(
            'Horde_Kolab_Session_Valid_Interface',
            $this->_getFactory()->getSessionValidator($session, $this->session_auth)
        );
    }

    public function testMethodValidateHasResultTrueIfTheSessionIsStillValid()
    {
        $factory = $this->_getFactory();
        $this->session_auth->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue('mail@example.org'));
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('mail@example.org'));
        $this->assertTrue($factory->validate($session));
    }

    public function testMethodCreatesessionHasResultHordekolabsessionstored()
    {
        $this->assertType('Horde_Kolab_Session_Decorator_Stored', $this->_getFactory()->createSession());
    }

    public function testMethodGetsessionHasResultHordekolabsessionTheOldSessionIfAnOldSessionWasStoredAndValid()
    {
        $factory = $this->_getFactory();
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('mail@example.org'));
        $this->session_storage->expects($this->once())
            ->method('load')
            ->will($this->returnValue($session));
        $this->session_auth->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue('mail@example.org'));
        $this->assertSame($session, $factory->getSession());
    }

    public function testMethodGetsessionHasResultHordekolabsessionANewSessionIfAnOldSessionWasStoredAndInvalid()
    {
        $factory = $this->_getFactory();
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('mail@example.org'));
        $this->session_storage->expects($this->once())
            ->method('load')
            ->will($this->returnValue($session));
        $this->session_auth->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue('new@example.org'));
        $this->assertTrue($session !== $factory->getSession());
    }

    public function testMethodGetsessionHasResultHordekolabsessionANewSessionIfNoOldSessionExisted()
    {
        $factory = $this->_getFactory();
        $this->session_storage->expects($this->once())
            ->method('load')
            ->will($this->returnValue(false));
        $this->assertType('Horde_Kolab_Session', $factory->getSession());
    }


    public function testMethodCreatesessionHasResultHordekolabsessionanonymousIfConfiguredThatWay()
    {
        $GLOBALS['conf']['kolab']['session']['anonymous']['user'] = 'anonymous';
        $GLOBALS['conf']['kolab']['session']['anonymous']['pass'] = '';
        $this->assertType(
            'Horde_Kolab_Session_Decorator_Anonymous',
            $this->_getFactory()->getSession()
        );
    }

    public function testMethodCreatesessionHasResultHordekolabsessionloggedIfConfiguredThatWay()
    {
        $GLOBALS['conf']['kolab']['session']['log'] = true;
        $this->assertType(
            'Horde_Kolab_Session_Decorator_Logged',
            $this->_getFactory()->getSession()
        );
    }

    public function testMethodGetsessionvalidatorHasResultHordekolabsessionvalidloggedIfConfiguredThatWay()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $auth = $this->getMock('Horde_Kolab_Session_Auth_Interface');
        $GLOBALS['conf']['kolab']['session']['log'] = true;
        $this->assertType(
            'Horde_Kolab_Session_Valid_Decorator_Logged',
            $this->_getFactory()->getSessionValidator($session, $auth)
        );
    }

    public function testMethodGetstorageHasresultSessionstorage()
    {
        $this->assertType(
            'Horde_Kolab_Session_Storage_Interface',
            $this->_getFactory()->getStorage()
        );
    }
}