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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the configuration based server factory.
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
class Horde_Kolab_Server_Class_Server_Factory_ConfigurationTest
extends PHPUnit_Framework_TestCase
{
    public function testMethodGetserverHasResultLoggedServerIfALoggerWasProvidedInTheConfiguration()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('logger' => 'set', 'basedn' => '')
        );
        $this->assertType('Horde_Kolab_Server_Logged', $factory->getServer());
    }

    public function testMethodGetserverHasResultMappedServerIfAMappedWasProvidedInTheConfiguration()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('map' => array(), 'basedn' => '')
        );
        $this->assertType('Horde_Kolab_Server_Mapped', $factory->getServer());
    }

    public function testMethodGetserverHasResultCleanerServerIfACleanedWasProvidedInTheConfiguration()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('cleanup' => true, 'basedn' => '')
        );
        $this->assertType('Horde_Kolab_Server_Cleaner', $factory->getServer());
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
            'Horde_Kolab_Server_Factory_Conn',
            $factory->getConnectionFactory()
        );
    }

    public function testMethodGetserverHasResultServer()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server',
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
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Connection',
            $factory->getConnection()
        );
    }

    public function testMethodGetcompositeHasResultServercomposite()
    {
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
            'Horde_Kolab_Server_Objects',
            $factory->getObjects()
        );
    }

    public function testMethodGetstructureHasresultServerstructure()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Structure',
            $factory->getStructure()
        );
    }

    public function testMethodGetsearchHasResultServersearch()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Search',
            $factory->getSearch()
        );
    }

    public function testMethodGetschemaHasResultServerschema()
    {
        $factory = new Horde_Kolab_Server_Factory_Configuration(
            array('basedn' => '')
        );
        $this->assertType(
            'Horde_Kolab_Server_Schema',
            $factory->getSchema()
        );
    }
}