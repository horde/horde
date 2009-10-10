<?php
/**
 * Test the server factory interface.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the server factory interface.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Server_FactoryTest extends Horde_Kolab_Server_Scenario
{
    public function testMethodSetupHasPostconditionThatAObjectHandlerOfTypeBaseIsBoundToObjects()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory::setup(array(), $injector);
        $this->assertType(
            'Horde_Kolab_Server_Objects_Base',
            $injector->getInstance('Horde_Kolab_Server_Objects')
        );
    }

    public function testMethodSetupHasPostconditionThatASchemaHandlerOfTypeBaseIsBoundToSchema()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory::setup(array(), $injector);
        $this->assertType(
            'Horde_Kolab_Server_Schema_Base',
            $injector->getInstance('Horde_Kolab_Server_Schema')
        );
    }

    public function testMethodSetupHasPostconditionThatASearchHandlerOfTypeBaseIsBoundToSearch()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory::setup(array(), $injector);
        $this->assertType(
            'Horde_Kolab_Server_Search_Base',
            $injector->getInstance('Horde_Kolab_Server_Search')
        );
    }

    public function testMethodSetupHasPostconditionThatAStructureOfTypeBaseIsBoundToStructure()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory::setup(array(), $injector);
        $this->assertType(
            'Horde_Kolab_Server_Structure_Kolab',
            $injector->getInstance('Horde_Kolab_Server_Structure')
        );
    }

    public function testMethodSetupHasPostconditionThatAStructureHandlerOfTypeLdapIsBoundToStructureIfConfiguredThatWay()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory::setup(
            array('structure' => array('driver' => 'ldap')),
            $injector
        );
        $this->assertType(
            'Horde_Kolab_Server_Structure_Ldap',
            $injector->getInstance('Horde_Kolab_Server_Structure')
        );
    }

    public function testMethodSetupHasPostconditionThatAServerOfTypeLdapIsBoundToServer()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory::setup(array(), $injector);
        $this->assertType(
            'Horde_Kolab_Server_Ldap',
            $injector->getInstance('Horde_Kolab_Server')
        );
    }

    public function testMethodSetupHasPostconditionThatAServerOfTypeLdapIsBoundToServerIfConfiguredThatWay()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory::setup(
            array('driver' => 'file', 'params' => array('file' => '/tmp/nix')),
            $injector
        );
        $this->assertType(
            'Horde_Kolab_Server_File',
            $injector->getInstance('Horde_Kolab_Server')
        );
    }

    public function testMethodSingletonReturnsTheSameInstanceWithTheSameParameters()
    {
        Horde_Kolab_Server_Factory::singleton();
    }

    public function testMethodSingletonReturnsDifferentInstancesWithDifferentParameters()
    {
        Horde_Kolab_Server_Factory::singleton();
    }
}