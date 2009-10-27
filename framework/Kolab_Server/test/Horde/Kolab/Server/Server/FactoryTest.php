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
class Horde_Kolab_Server_Server_FactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete();
        $this->injector = new Horde_Injector(new Horde_Injector_TopLevel());
    }

    public function testMethodSetupHasPostconditionThatAObjectHandlerOfTypeBaseIsBoundToObjects()
    {
        Horde_Kolab_Server_Factory::setup($this->injector, array());
        $this->assertType(
            'Horde_Kolab_Server_Objects_Base',
            $this->injector->getInstance('Horde_Kolab_Server_Objects')
        );
    }

    public function testMethodSetupHasPostconditionThatASchemaHandlerOfTypeBaseIsBoundToSchema()
    {
        Horde_Kolab_Server_Factory::setup($this->injector, array());
        $this->assertType(
            'Horde_Kolab_Server_Schema_Base',
            $this->injector->getInstance('Horde_Kolab_Server_Schema')
        );
    }

    public function testMethodSetupHasPostconditionThatASearchHandlerOfTypeBaseIsBoundToSearch()
    {
        Horde_Kolab_Server_Factory::setup($this->injector, array());
        $this->assertType(
            'Horde_Kolab_Server_Search_Base',
            $this->injector->getInstance('Horde_Kolab_Server_Search')
        );
    }

    public function testMethodSetupHasPostconditionThatAStructureOfTypeKolabIsBoundToStructure()
    {
        Horde_Kolab_Server_Factory::setup($this->injector, array());
        $this->assertType(
            'Horde_Kolab_Server_Structure_Kolab',
            $this->injector->getInstance('Horde_Kolab_Server_Structure')
        );
    }

    public function testMethodSetupHasPostconditionThatAStructureHandlerOfTypeLdapIsBoundToStructureIfConfiguredThatWay()
    {
        Horde_Kolab_Server_Factory::setup(
            $this->injector,
            array('structure' => array('driver' => 'ldap'))
        );
        $this->assertType(
            'Horde_Kolab_Server_Structure_Ldap',
            $this->injector->getInstance('Horde_Kolab_Server_Structure')
        );
    }

    public function testMethodSetupHasPostconditionThatAServerOfTypeLdapIsBoundToServer()
    {
        Horde_Kolab_Server_Factory::setup($this->injector, array('basedn' => 'dc=example,dc=com'));
        $this->assertType(
            'Horde_Kolab_Server_Ldap_Standard',
            $this->injector->getInstance('Horde_Kolab_Server')
        );
    }

    public function testMethodSetupHasPostconditionThatAServerOfTypeFileIsBoundToServerIfConfiguredThatWay()
    {
        Horde_Kolab_Server_Factory::setup(
            $this->injector,
            array('driver' => 'file', 'params' => array('basedn' => '', 'file' => '/tmp/nix'))
        );
        $this->assertType(
            'Horde_Kolab_Server_Ldap_Standard',
            $this->injector->getInstance('Horde_Kolab_Server')
        );
    }

    public function testMethodSetupHasPostconditionThatAServerOfTypeFilteredLdapIsBoundToServerIfAFilterHasBeenProvided()
    {
        Horde_Kolab_Server_Factory::setup(
            $this->injector,
            array('basedn' => 'dc=example,dc=com', 'filter' => 'test')
        );
        $this->assertType(
            'Horde_Kolab_Server_Ldap_Filtered',
            $this->injector->getInstance('Horde_Kolab_Server')
        );
    }

    public function testMethodSetupHasPostconditionThatAMappedServerIsBoundToServerIfAMapHasBeenProvided()
    {
        Horde_Kolab_Server_Factory::setup(
            $this->injector,
            array(
                'basedn' => 'dc=example,dc=com',
                'map' => array('a' => 'b'),
            )
        );
        $this->assertType(
            'Horde_Kolab_Server_Mapped',
            $this->injector->getInstance('Horde_Kolab_Server')
        );
    }

    public function testMethodSetupHasPostconditionThatALoggedServerIsBoundToServerIfALoggerHasBeenProvided()
    {
        Horde_Kolab_Server_Factory::setup(
            $this->injector,
            array(
                'basedn' => 'dc=example,dc=com',
                'logger' => $this->getMock('Horde_Log_Logger'),
            )
        );
        $this->assertType(
            'Horde_Kolab_Server_Logged',
            $this->injector->getInstance('Horde_Kolab_Server')
        );
    }

    public function testMethodGetserverHasPostconditionThatTheConnectionParametersGetRewrittenIfNecessary()
    {
        Horde_Kolab_Server_Factory::setup(
            $this->injector,
            array(
                'server' => 'localhost',
                'phpdn' => 'a',
                'phppw' => 'a',
                'basedn' => 'dc=example,dc=com'
            )
        );
        $this->injector->getInstance('Horde_Kolab_Server');
        /**@todo: Actually not testable as we can't read the configuration again */
    }

    public function testMethodGetserverHasPostconditionThatTheConnectionIsSplittedIfRequired()
    {
        Horde_Kolab_Server_Factory::setup(
            $this->injector,
            array(
                'host' => 'localhost',
                'host_master' => 'writehost',
                'basedn' => 'dc=example,dc=com'
            )
        );
        $this->injector->getInstance('Horde_Kolab_Server');
        /**@todo: Actually not testable as we can't read the configuration again */
    }

    public function testMethodGetserverThrowsExceptionIfTheBasednIsMissing()
    {
        try {
            Horde_Kolab_Server_Factory::setup(
                $this->injector,
                array('dummy')
            );
            $this->injector->getInstance('Horde_Kolab_Server');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(
                'The base DN is missing', $e->getMessage()
            );
        }
    }

    public function testMethodGetserverThrowsExceptionIfTheDriverIsUnknown()
    {
        try {
            Horde_Kolab_Server_Factory::setup(
                $this->injector,
                array('driver' => 'unknown')
            );
            $this->injector->getInstance('Horde_Kolab_Server');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(
                'Invalid server configuration!', $e->getMessage()
            );
        }
    }

    public function testMethodSingletonReturnsTheSameInstanceWithTheSameParameters()
    {
        $result1 = Horde_Kolab_Server_Factory::singleton(array('basedn' => 'dc=example,dc=com'));
        $result2 = Horde_Kolab_Server_Factory::singleton(array('basedn' => 'dc=example,dc=com'));
        $this->assertSame($result1, $result2);
    }

    public function testMethodSingletonReturnsDifferentInstancesWithDifferentParameters()
    {
        global $conf;
        $conf['kolab']['ldap']['basedn'] = 'test';
        $result1 = Horde_Kolab_Server_Factory::singleton(array('basedn' => 'dc=example,dc=com'));
        $result2 = Horde_Kolab_Server_Factory::singleton();
        $this->assertTrue($result1 !== $result2);
    }
}