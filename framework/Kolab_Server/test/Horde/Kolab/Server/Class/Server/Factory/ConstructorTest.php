<?php
/**
 * Test the mapping server factory.
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
 * Test the mapping server factory.
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
class Horde_Kolab_Server_Class_Server_Factory_ConstructorTest
extends Horde_Kolab_Server_LdapTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->factory   = $this->getMock(
            'Horde_Kolab_Server_Factory_Connection_Interface'
        );
        $this->objects   = $this->getMock(
            'Horde_Kolab_Server_Objects_Interface'
        );
        $this->structure = $this->getMock(
            'Horde_Kolab_Server_Structure_Interface'
        );
        $this->search    = $this->getMock(
            'Horde_Kolab_Server_Search_Interface'
        );
        $this->schema    = $this->getMock(
            'Horde_Kolab_Server_Schema_Interface'
        );
    }

    public function testMethodConstructHasParametersFactoryObjectsStructureSearchSchemaConfig()
    {
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
    }

    public function testMethodGetserverReturnsServer()
    {
        $this->skipIfNoLdap();
        $this->factory->expects($this->once())
            ->method('getConnection')
            ->will(
                $this->returnValue(
                    $this->getMock(
                        'Horde_Kolab_Server_Connection_Interface'
                    )
                )
            );
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
        $this->assertType('Horde_Kolab_Server_Interface', $factory->getServer());
    }

    public function testMethodGetconfigurationReturnsArrayConfiguration()
    {
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
        $this->assertEquals(
            array('basedn' => 'test'), $factory->getConfiguration()
        );
    }

    public function testMethodGetconnectionGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConnection')
            ->will(
                $this->returnValue(
                    $this->getMock('Horde_Kolab_Server_Connection')
                )
            );
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
        $this->assertType(
            'Horde_Kolab_Server_Connection',
            $factory->getConnection()
        );
    }

    public function testMethodGetcompositeReturnsComposite()
    {
        $this->skipIfNoLdap();
        $this->factory->expects($this->once())
            ->method('getConnection')
            ->will(
                $this->returnValue(
                    $this->getMock(
                        'Horde_Kolab_Server_Connection_Interface'
                    )
                )
            );
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
        $this->assertType(
            'Horde_Kolab_Server_Composite_Interface',
            $factory->getComposite()
        );
    }

    public function testMethodGetobjectsReturnsObjects()
    {
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
        $this->assertSame($this->objects, $factory->getObjects());
    }

    public function testMethodGetstructureReturnsStructure()
    {
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
        $this->assertSame($this->structure, $factory->getStructure());
    }

    public function testMethodGetsearchReturnsSearch()
    {
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
        $this->assertSame($this->search,  $factory->getSearch());
    }

    public function testMethodGetschemaGetsDelegated()
    {
        $factory = new Horde_Kolab_Server_Factory_Constructor(
            $this->factory, $this->objects, $this->structure,
            $this->search, $this->schema, array('basedn' => 'test')
        );
        $this->assertSame($this->schema,  $factory->getSchema());
    }
}