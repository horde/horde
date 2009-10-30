<?php
/**
 * Test the ldap connection factory.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../../../../LdapTestCase.php';

/**
 * Test the ldap connection factory.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Factory_Conn_LdapTest
extends Horde_Kolab_Server_LdapTestCase
{
    public function testMethodSetconfigurationHasPostconditionThatTheServerParameterWasRewritten()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Ldap();
        $factory->setConfiguration(
            array(
                'basedn' => 'test',
                'server' => '1'
            )
        );
        $this->assertEquals(
            array(
                'basedn' => 'test',
                'host' => '1'
            ),
            $factory->getConfiguration()
        );
    }

    public function testMethodSetconfigurationHasPostconditionThatThePhpdnParameterWasRewritten()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Ldap();
        $factory->setConfiguration(
            array(
                'basedn' => 'test',
                'phpdn' => '1'
            )
        );
        $this->assertEquals(
            array(
                'basedn' => 'test',
                'binddn' => '1'
            ),
            $factory->getConfiguration()
        );
    }

    public function testMethodSetconfigurationHasPostconditionThatThePhppwParameterWasRewritten()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Ldap();
        $factory->setConfiguration(
            array(
                'basedn' => 'test',
                'phppw' => '1'
            )
        );
        $this->assertEquals(
            array(
                'basedn' => 'test',
                'bindpw' => '1'
            ),
            $factory->getConfiguration()
        );
    }

    public function testMethodSetconfigurationThrowsExceptionIfTheBasednIsNotSet()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Ldap();
        try {
            $factory->setConfiguration(array());
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(
                'The base DN is missing!',
                $e->getMessage()
            );
        }
    }

    public function testMethodGetconnectionHasResultConnectionSimpleldap()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Ldap();
        $factory->setConfiguration(array('basedn' => 'test'));
        $this->assertType(
            'Horde_Kolab_Server_Connection_Simpleldap',
            $factory->getConnection()
        );
    }

    public function testMethodGetconnectionHasResultConnectionSplittedldapIfTheHostMasterIsSet()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Ldap();
        $factory->setConfiguration(array('basedn' => 'test', 'host_master' => 'dummy'));
        $this->assertType(
            'Horde_Kolab_Server_Connection_Splittedldap',
            $factory->getConnection()
        );
    }
}