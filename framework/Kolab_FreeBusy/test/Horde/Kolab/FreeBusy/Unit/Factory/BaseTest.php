<?php
/**
 * Test the base factory.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the base factory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Factory_BaseTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testCreateUser()
    {
        $user = $this->_getFactory()->createUser();
        $this->assertEquals('z@example.org', $user->getPrimaryId());
    }

    public function testCreateOwner()
    {
        $owner = $this->_getFactory()->createOwner();
        $this->assertEquals('new@example.org', $owner->getPrimaryId());
    }

    public function testRemote()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Provider_Remote_PassThrough',
            $this->_getFactory('remote')->createProvider()
        );
    }

    public function testRemoteRedirect()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Provider_Remote_Redirect',
            $this->_getFactory(
                'remote',
                array(
                    'provider' => array(
                        'redirect' => true
                    )
                )
            )->createProvider()
        );
    }

    public function testLocal()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Provider_Local',
            $this->_getFactory()->createProvider()
        );
    }

    public function testRemoteAsLocal()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Provider_Local',
            $this->_getFactory(
                'remote',
                array(
                    'provider' => array(
                        'server' => 'https://example.com/freebusy'
                    )
                )
                
            )->createProvider()
        );
    }

    public function testRemoteLog()
    {
        $this->_getFactory('remote')->createProvider();
        $this->assertLogContains(
            "URL \"https://example.com/freebusy\" indicates remote free/busy server since we only offer \"https://localhost/export\". Redirecting."
        );
    }

    private function _getFactory($owner = 'new', $params = array())
    {
        $injector = $this->getInjector();
        $injector->setInstance(
            'Horde_Controller_Request',
            new Horde_Controller_Request_Mock(
                array('server' => array('PHP_AUTH_USER' => 'y'))
            )
        );
        $injector->setInstance(
            'Horde_Kolab_FreeBusy_UserDb', $this->getDb()
        );
        $injector->setInstance(
            'Horde_Log_Logger', $this->getMockLogger()
        );
        $injector->bindFactory(
            'Horde_Kolab_FreeBusy_User',
            'Horde_Kolab_FreeBusy_Freebusy_Factory_Base',
            'createUser'
        );
        $injector->bindFactory(
            'Horde_Kolab_FreeBusy_Owner',
            'Horde_Kolab_FreeBusy_Freebusy_Factory_Base',
            'createOwner'
        );
        $injector->setInstance('Horde_Kolab_FreeBusy_Configuration', $params);
        $injector->setInstance(
            'Horde_Kolab_FreeBusy_Controller_MatchDict',
            $this->getStubDict(array('owner' => $owner))
        );
        return $injector->getInstance('Horde_Kolab_FreeBusy_Freebusy_Factory_Base');
    }

}