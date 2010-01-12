<?php
/**
 * Test the log decorator factory.
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
 * Test the log decorator factory.
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
class Horde_Kolab_Session_Class_Factory_Decorator_LoggedTest
extends Horde_Kolab_Session_SessionTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setupLogger();
    }

    public function testMethodCreatesessionHasResultHordekolabsessionlogged()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('createSession')
            ->will($this->returnValue($session));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType(
            'Horde_Kolab_Session_Decorator_Logged',
            $factory->createSession()
        );
    }

    public function testMethodGetsessionvalidatorHasResultHordekolabsessionvalidlogged()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $auth = $this->getMock('Horde_Kolab_Session_Auth_Interface');
        $validator = $this->getMock('Horde_Kolab_Session_Valid_Interface');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('getSessionValidator')
            ->will($this->returnValue($validator));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType(
            'Horde_Kolab_Session_Valid_Decorator_Logged',
            $factory->getSessionValidator($session, $auth)
        );
    }

    public function testMethodGetserverGetsDelegated()
    {
        $server = $this->getMock('Horde_Kolab_Server');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('getServer')
            ->will($this->returnValue($server));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType('Horde_Kolab_Server', $factory->getServer());
    }

    public function testMethodGetsessionauthGetsDelegated()
    {
        $auth = $this->getMock('Horde_Kolab_Session_Auth');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('getSessionAuth')
            ->will($this->returnValue($auth));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType(
            'Horde_Kolab_Session_Auth',
            $factory->getSessionAuth()
        );
    }

    public function testMethodGetsessionconfigurationGetsDelegated()
    {
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('getSessionConfiguration')
            ->will($this->returnValue(array()));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType('array', $factory->getSessionConfiguration());
    }

    public function testMethodGetsessionstorageGetsDelegated()
    {
        $storage = $this->getMock('Horde_Kolab_Session_Storage');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('getSessionStorage')
            ->will($this->returnValue($storage));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType(
            'Horde_Kolab_Session_Storage',
            $factory->getSessionStorage()
        );
    }

    public function testMethodGetsessionvalidatorGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $auth = $this->getMock('Horde_Kolab_Session_Auth_Interface');
        $validator = $this->getMock('Horde_Kolab_Session_Valid_Interface');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('getSessionValidator')
            ->will($this->returnValue($validator));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType(
            'Horde_Kolab_Session_Valid_Interface',
            $factory->getSessionValidator($session, $auth)
        );
    }

    public function testMethodValidateGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(true));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertTrue($factory->validate($session, 'test'));
    }

    public function testMethodCreatesessionGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('createSession')
            ->will($this->returnValue($session));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType('Horde_Kolab_Session_Interface', $factory->createSession());
    }

    public function testMethodGetsessionGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $factory = $this->getMock('Horde_Kolab_Session_Factory_Interface');
        $factory->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));
        $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
            $factory, $this->logger
        );
        $this->assertType('Horde_Kolab_Session_Interface', $factory->getSession());
    }
}