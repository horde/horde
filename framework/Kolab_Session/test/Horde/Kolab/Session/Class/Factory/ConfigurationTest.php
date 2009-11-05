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

    public function testMethodConstructThrowsExceptionIfTheServerConfigurationIsMissing()
    {
        try {
            $factory = new Horde_Kolab_Session_Factory_Configuration(array());
            $this->fail('No exception!');
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->assertEquals(
                'The Kolab server configuration is missing!',
                $e->getMessage()
            );
        }
    }

    public function testMethodCreatesessionHasResultHordekolabsessionanonymousIfConfiguredThatWay()
    {
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'session' => array(
                    'anonymous' => array(
                        'user' => 'anonymous',
                        'pass' => ''
                    )
                ),
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Session_Decorator_Anonymous',
            $factory->createSession()
        );
    }

    public function testMethodCreatesessionHasResultHordekolabsessionloggedIfConfiguredThatWay()
    {
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'logger' => $this->logger,
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Session_Decorator_Logged',
            $factory->createSession()
        );
    }

    public function testMethodGetsessionvalidatorHasResultHordekolabsessionvalidloggedIfConfiguredThatWay()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $auth = $this->getMock('Horde_Kolab_Session_Auth_Interface');
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'logger' => $this->logger,
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Session_Valid_Decorator_Logged',
            $factory->getSessionValidator($session, $auth)
        );
    }

    public function testMethodGetserverHasResultServercomposite()
    {
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Server_Composite_Interface',
            $factory->getServer()
        );
    }

    public function testMethodGetsessionauthHasResultSessionauth()
    {
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Session_Auth_Interface',
            $factory->getSessionAuth()
        );
    }

    public function testMethodGetsessionconfigurationHasResultArray()
    {
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType('array', $factory->getSessionConfiguration());
    }

    public function testMethodGetsessionstorageHasresultSessionstorage()
    {
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Session_Storage_Interface',
            $factory->getSessionStorage()
        );
    }

    public function testMethodGetsessionvalidatorHasResultSessionvalid()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $auth = $this->getMock('Horde_Kolab_Session_Auth_Interface');
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Session_Valid_Interface',
            $factory->getSessionValidator($session, $auth)
        );
    }

    public function testMethodValidateHasResultBooleanTrueIfTheSessionIsStillValid()
    {
        $session = $this->getMock('Horde_Kolab_Session_Interface');
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertTrue($factory->validate($session, ''));
    }

    public function testMethodCreatesessionHasResultSession()
    {
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'server' => array(
                    'basedn' => ''
                )
            )
        );
        $this->assertType('Horde_Kolab_Session_Interface', $factory->createSession());
    }

    public function testMethodGetsessionHasResultSession()
    {
        $factory = new Horde_Kolab_Session_Factory_Configuration(
            array(
                'server' => array(
                    'mock' => true,
                    'basedn' => '',
                    'data' => array(
                        'dn=user' => array(
                            'dn' => 'dn=user',
                            'data' => array(
                                'uid' => array(''),
                                'mail' => array('user@example.org'),
                                'userPassword' => array(''),
                                'objectClass' => array('top', 'kolabInetOrgPerson'),
                            )
                        )
                    )
                )
            )
        );
        $this->assertType('Horde_Kolab_Session_Interface', $factory->getSession());
    }
}