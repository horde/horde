<?php
/**
 * Tests the list toolset handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Tests the list toolset handler.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_List_ToolsTest
extends PHPUnit_Framework_TestCase
{
    public function testManipulation()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array()
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Manipulation',
            $tools->getListManipulation()
        );
    }

    public function testLoggedManipulation()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array(
                'logger' => $this->getMock('Horde_Log_Logger')
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Manipulation_Decorator_Log',
            $tools->getListManipulation()
        );
    }

    public function testSynchronization()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array()
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Synchronization',
            $tools->getListSynchronization()
        );
    }

    public function testLoggedSynchronization()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array(
                'logger' => $this->getMock('Horde_Log_Logger')
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Synchronization_Decorator_Log',
            $tools->getListSynchronization()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_List_Exception
     */
    public function testInvalidQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array()
        );
        $tools->getQuery('TEST');
    }

    /**
     * @expectedException Horde_Kolab_Storage_List_Exception
     */
    public function testMissingQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array()
        );
        $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_SHARE);
    }

    /**
     * @expectedException Horde_Kolab_Storage_List_Exception
     */
    public function testInvalidQueryset()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array('queryset' => 'TEST')
        );
    }

    public function testDefaultQueries()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array()
        );
        $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_BASE);
    }

    public function testListQuerysetBase()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array('queryset' => Horde_Kolab_Storage_List_Tools::QUERYSET_BASIC)
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_List',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_BASE)
        );
    }

    public function testListQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array('queries' => array(Horde_Kolab_Storage_List_Tools::QUERY_BASE))
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_List',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_BASE)
        );
    }

    public function testAclQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array(
                'queries' => array(
                    Horde_Kolab_Storage_List_Tools::QUERY_BASE,
                    Horde_Kolab_Storage_List_Tools::QUERY_ACL
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_Acl',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_ACL)
        );
    }

    public function testShareQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->getMock('Horde_Kolab_Storage_Driver'),
            array(
                'queryset' => Horde_Kolab_Storage_List_Tools::QUERYSET_HORDE,
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_Share',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_SHARE)
        );
    }

    public function testGetId()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('ID'));
        $tools = new Horde_Kolab_Storage_List_Tools($driver);
        $this->assertEquals('ID', $tools->getId());
    }

    public function testGetNamespace()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue('NAMESPACE'));
        $tools = new Horde_Kolab_Storage_List_Tools($driver);
        $this->assertEquals('NAMESPACE', $tools->getNamespace());
    }
}