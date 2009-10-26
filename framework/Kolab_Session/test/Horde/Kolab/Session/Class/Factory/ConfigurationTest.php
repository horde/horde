<?php
/**
 * Test the configuration based factory.
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
 * Test the configuration based factory.
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
class Horde_Kolab_Session_Class_Factory_ConfigurationTest extends Horde_Kolab_Session_SessionTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setupLogger();
    }

    public function testMethodCreatesessionHasResultHordekolabsessionanonymousIfConfiguredThatWay()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('createSession')
            ->will($this->returnValue($session));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory,
            array(
                'session' => array(
                    'anonymous' => array(
                        'user' => 'anonymous',
                        'pass' => ''
                    )
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Session_Anonymous',
            $factory->createSession()
        );
    }

    public function testMethodCreatesessionHasResultHordekolabsessionloggedIfConfiguredThatWay()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('createSession')
            ->will($this->returnValue($session));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array('logger' => $this->logger)
        );
        $this->assertType(
            'Horde_Kolab_Session_Logged',
            $factory->createSession()
        );
    }

    public function testMethodGetsessionvalidatorHasResultHordekolabsessionvalidloggedIfConfiguredThatWay()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $auth = $this->getMock('Horde_Kolab_Session_Auth');
        $validator = $this->getMock('Horde_Kolab_Session_Valid');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('getSessionValidator')
            ->will($this->returnValue($validator));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory,  array('logger' => $this->logger)
        );
        $this->assertType(
            'Horde_Kolab_Session_Valid_Logged',
            $factory->getSessionValidator($session, $auth)
        );
    }

    public function testMethodGetserverGetsDelegated()
    {
        $server = $this->getMock('Horde_Kolab_Server');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('getServer')
            ->will($this->returnValue($server));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array()
        );
        $this->assertType('Horde_Kolab_Server', $factory->getServer());
    }

    public function testMethodGetsessionauthGetsDelegated()
    {
        $auth = $this->getMock('Horde_Kolab_Session_Auth');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('getSessionAuth')
            ->will($this->returnValue($auth));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array()
        );
        $this->assertType(
            'Horde_Kolab_Session_Auth',
            $factory->getSessionAuth()
        );
    }

    public function testMethodGetsessionconfigurationGetsDelegated()
    {
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('getSessionConfiguration')
            ->will($this->returnValue(array()));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array()
        );
        $this->assertType('array', $factory->getSessionConfiguration());
    }

    public function testMethodGetsessionstorageGetsDelegated()
    {
        $storage = $this->getMock('Horde_Kolab_Session_Storage');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('getSessionStorage')
            ->will($this->returnValue($storage));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array()
        );
        $this->assertType(
            'Horde_Kolab_Session_Storage',
            $factory->getSessionStorage()
        );
    }

    public function testMethodGetsessionvalidatorGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $auth = $this->getMock('Horde_Kolab_Session_Auth');
        $validator = $this->getMock('Horde_Kolab_Session_Valid');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('getSessionValidator')
            ->will($this->returnValue($validator));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array()
        );
        $this->assertType(
            'Horde_Kolab_Session_Valid',
            $factory->getSessionValidator($session, $auth)
        );
    }

    public function testMethodValidateGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(true));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array()
        );
        $this->assertTrue($factory->validate($session, 'test'));
    }

    public function testMethodCreatesessionGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('createSession')
            ->will($this->returnValue($session));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array()
        );
        $this->assertType('Horde_Kolab_Session', $factory->createSession());
    }

    public function testMethodGetsessionGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $factory = $this->getMock('Horde_Kolab_Session_Factory');
        $factory->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            $factory, array()
        );
        $this->assertType('Horde_Kolab_Session', $factory->getSession());
    }
}