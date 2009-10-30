<?php
/**
 * Test the configuration based connection factory.
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
 * Test the configuration based connection factory.
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
class Horde_Kolab_Server_Class_Server_Factory_Conn_ConfigurationTest
extends Horde_Kolab_Server_LdapTestCase
{
    public function testMethodConstructHasParameterArrayConfiguration()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Configuration(
            array('basedn' => 'a')
        );
    }

    public function testMethodConstructHasPostconditionThatTheConfigurationWasSaved()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Configuration(
            array('basedn' => 'a')
        );
        $this->assertEquals(array('basedn' => 'a'), $factory->getConfiguration());
    }

    public function testMethodConstructHasResultArrayTheConfiguration()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Configuration(
            array('basedn' => 'a')
        );
        $this->assertType('array', $factory->getConfiguration());
    }

    public function testMethodConstructHasPostconditionThatTheConnectionFactoryHasBeenSet()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Configuration(
            array('mock' => true)
        );
        $this->assertType('Horde_Kolab_Server_Connection_Mock', $factory->getConnection());
    }

    public function testMethodGetconnectionHasResultMockConnectionIfConfiguredThatWay()
    {
        $this->testMethodConstructHasPostconditionThatTheConnectionFactoryHasBeenSet();
    }

    public function testMethodGetconnectionHasResultLdapConnectionIfConfiguredThatWay()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Configuration(
            array('basedn' => 'a')
        );
        $this->assertType('Horde_Kolab_Server_Connection_Simpleldap', $factory->getConnection());
    }
}