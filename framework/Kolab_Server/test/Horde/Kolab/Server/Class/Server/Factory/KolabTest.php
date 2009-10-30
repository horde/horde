<?php
/**
 * Test the default Kolab server factory.
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
 * Test the default Kolab server factory.
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
class Horde_Kolab_Server_Class_Server_Factory_KolabTest
extends Horde_Kolab_Server_LdapTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->conn_factory = $this->getMock('Horde_Kolab_Server_Factory_Conn');
        $this->connection = $this->getMock('Horde_Kolab_Server_Connection');
    }

    public function testMethodConstructHasParametersConnectionfactoryAndArrayParameters()
    {
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array()
        );
    }

    public function testMethodGetconnectionfactoryHasResultTheStoredConnectionfactory()
    {
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array()
        );
        $this->assertSame($this->conn_factory, $factory->getConnectionFactory());
    }

    public function testMethodGetconfigurationHasResultTheStoredConfigurationParameters()
    {
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array('a' => 'a')
        );
        $this->assertEquals(array('a' => 'a'), $factory->getConfiguration());
    }

    public function testMethodGetconnectionHasResultConnection()
    {
        $this->conn_factory->expects($this->once())
            ->method('setConfiguration')
            ->with(array());
        $this->conn_factory->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array()
        );
        $this->assertType(
            'Horde_Kolab_Server_Connection',
            $factory->getConnection()
        );
    }

    public function testMethodGetobjectsHasResultObjectsbase()
    {
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array()
        );
        $this->assertType(
            'Horde_Kolab_Server_Objects_Base',
            $factory->getObjects()
        );
    }

    public function testMethodGetsearchHasResultSearchbase()
    {
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array()
        );
        $this->assertType(
            'Horde_Kolab_Server_Search_Base',
            $factory->getSearch()
        );
    }

    public function testMethodGetsearchHasResultSchemabase()
    {
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array()
        );
        $this->assertType(
            'Horde_Kolab_Server_Schema_Base',
            $factory->getSchema()
        );
    }

    public function testMethodGetstructureHasResultStructurekolab()
    {
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array()
        );
        $this->assertType(
            'Horde_Kolab_Server_Structure_Kolab',
            $factory->getStructure()
        );
    }

    public function testMethodGetserverHasResultServerldapstandard()
    {
        $this->conn_factory->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array('basedn' => 'test')
        );
        $this->assertType(
            'Horde_Kolab_Server_Ldap_Standard',
            $factory->getServer()
        );
    }

    public function testMethodGetserverHasResultServerldapfilteredIfTheFilterOptionIsSet()
    {
        $this->conn_factory->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array('basedn' => 'test', 'filter' => 'a')
        );
        $this->assertType(
            'Horde_Kolab_Server_Ldap_Filtered',
            $factory->getServer()
        );
    }

    public function testMethodGetserverThrowsExceptionIfTheBasednIsMissingInTheConfiguration()
    {
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array()
        );
        try {
            $factory->getServer();
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('The base DN is missing', $e->getMessage());
        }
    }

    public function testMethodGetcompositeHasResultComposite()
    {
        $this->conn_factory->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));
        $factory = new Horde_Kolab_Server_Factory_Kolab(
            $this->conn_factory, array('basedn' => 'test')
        );
        $this->assertType(
            'Horde_Kolab_Server_Composite',
            $factory->getComposite()
        );
    }


}