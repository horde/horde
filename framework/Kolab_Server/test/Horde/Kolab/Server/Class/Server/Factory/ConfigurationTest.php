<?php
/**
 * Test the configuration based server factory.
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
require_once dirname(__FILE__) . '/../../../LdapTestCase.php';

/**
 * Test the configuration based server factory.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Class_Server_Factory_ConfigurationTest
extends Horde_Kolab_Server_LdapTestCase
{
    public function testMethodGetserverHasResultLoggedServerIfALoggerWasProvidedInTheConfiguration()
    {
        $this->skipIfNoLdap();
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('logger' => 'set', 'basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Decorator_Log', $factory->getServer()
        );
    }

    public function testMethodGetserverHasResultMappedServerIfAMappedWasProvidedInTheConfiguration()
    {
        $this->skipIfNoLdap();
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('map' => array(), 'basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Decorator_Map', $factory->getServer()
        );
    }

    public function testMethodGetserverHasResultCleanerServerIfACleanedWasProvidedInTheConfiguration()
    {
        $this->skipIfNoLdap();
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('cleanup' => true, 'basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Decorator_Clean', $factory->getServer()
        );
    }

    public function testMethodConstructHasParametersArrayParameters()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
    }

    public function testMethodGetconnectionfactoryHasResultServerconnectionfactory()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Factory_Connection_Interface',
            $factory->getConnectionFactory()
        );
    }

    public function testMethodGetserverHasResultServer()
    {
        $this->skipIfNoLdap();
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Interface',
            $factory->getServer()
        );
    }

    public function testMethodGetconfigurationHasResultArray()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'array',
            $factory->getConfiguration()
        );
    }

    public function testMethodGetconnectionHasResultServerconnection()
    {
        $this->skipIfNoLdap();
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Connection_Interface',
            $factory->getConnection()
        );
    }

    public function testMethodGetcompositeHasResultServercomposite()
    {
        $this->skipIfNoLdap();
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Composite',
            $factory->getComposite()
        );
    }

    public function testMethodGetobjectsHasResultServerobjects()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Objects_Interface',
            $factory->getObjects()
        );
    }

    public function testMethodGetstructureHasresultServerstructure()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Structure_Interface',
            $factory->getStructure()
        );
    }

    public function testMethodGetsearchHasResultServersearch()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Search_Interface',
            $factory->getSearch()
        );
    }

    public function testMethodGetschemaHasResultServerschema()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Schema_Interface',
            $factory->getSchema()
        );
    }
}