<?php
/**
 * Test the injector based server factory.
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
 * Test the injector based server factory.
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
class Horde_Kolab_Server_Class_Server_Factory_InjectorTest
extends Horde_Kolab_Server_LdapTestCase
{
    private function _getFactory(array $configuration = array())
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory_Injector::setup(
            'Horde_Kolab_Server_Factory_Conn_Mock',
            $configuration,
            $injector
        );
        return $injector->getInstance(
            'Horde_Kolab_Server_Factory_Injector'
        );
    }

    public function testMethodGetconnectionfactoryHasResultConnectionfactory()
    {
        $this->assertType(
            'Horde_Kolab_Server_Factory_Conn',
            $this->_getFactory(array())->getConnectionFactory()
        );
    }

    public function testMethodGetconnectionHasResultConnection()
    {
        $factory = $this->_getFactory(array());
        $this->assertType(
            'Horde_Kolab_Server_Connection',
            $factory->getConnection()
        );
    }

    public function testMethodGetserverHasResultServerldapstandard()
    {
        $this->skipIfNoLdap();
        $factory = $this->_getFactory(array('basedn' => 'test'));
        $this->assertType(
            'Horde_Kolab_Server_Ldap_Standard',
            $factory->getServer()
        );
    }

    public function testMethodGetserverThrowsExceptionIfTheBaseDnIsMissingInTheConfiguration()
    {
        $factory = $this->_getFactory(array());
        try {
            $factory->getServer();
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(
                'The base DN is missing!',
                $e->getMessage()
            );
        }
    }

    public function testMethodGetserverHasResultServerldapFilteredIfAFilterWasSet()
    {
        $this->skipIfNoLdap();
        $factory = $this->_getFactory(array('filter' => 'test', 'basedn' => 'test'));
        $this->assertType(
            'Horde_Kolab_Server_Ldap_Filtered',
            $factory->getServer()
        );
    }

    public function testMethodGetobjectsHasResultObjects()
    {
        $factory = $this->_getFactory(array());
        $this->assertType(
            'Horde_Kolab_Server_Objects',
            $factory->getObjects()
        );
    }

    public function testMethodGetstructureHasResultStructureKolab()
    {
        $factory = $this->_getFactory(array());
        $this->assertType(
            'Horde_Kolab_Server_Structure_Kolab',
            $factory->getStructure()
        );
    }

    public function testMethodGetstructureHasResultStructureLdapIfConfiguredThatWay()
    {
        $factory = $this->_getFactory(
            array(
                'structure' => array(
                    'driver' => 'Horde_Kolab_Server_Structure_Ldap'
                )
            )
        );
        $this->assertType(
            'Horde_Kolab_Server_Structure_Ldap',
            $factory->getStructure()
        );
    }

    public function testMethodGetsearchHasResultSearch()
    {
        $factory = $this->_getFactory(array());
        $this->assertType(
            'Horde_Kolab_Server_Search',
            $factory->getSearch()
        );
    }

    public function testMethodGetschemaHasResultSchema()
    {
        $factory = $this->_getFactory(array());
        $this->assertType(
            'Horde_Kolab_Server_Schema',
            $factory->getSchema()
        );
    }

    public function testMethodGetcompositeHasResultComposite()
    {
        $this->skipIfNoLdap();
        $factory = $this->_getFactory(array('basedn' => 'test'));
        $this->assertType(
            'Horde_Kolab_Server_Composite',
            $factory->getComposite()
        );
    }

}