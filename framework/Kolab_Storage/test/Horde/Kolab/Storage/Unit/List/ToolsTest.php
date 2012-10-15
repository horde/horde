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
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array()
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Manipulation',
            $tools->getListManipulation()
        );
    }

    public function testDebugLogsManipulation()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'log' => array('debug')
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Manipulation_Decorator_Log',
            $tools->getListManipulation()
        );
    }

    public function testSpecificallyLogManipulation()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'log' => array('list_manipulation')
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
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array()
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Synchronization',
            $tools->getListSynchronization()
        );
    }

    public function testDebugLogsSynchronization()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'log' => array('debug')
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Synchronization_Decorator_Log',
            $tools->getListSynchronization()
        );
    }

    public function testSpecificallyLogSynchronization()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'log' => array('list_synchronization')
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
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
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
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array()
        );
        $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_SHARE);
    }

    public function testDefaultQueries()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array()
        );
        $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_BASE);
    }

    public function testListQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array('queries' => array(Horde_Kolab_Storage_List_Tools::QUERY_BASE))
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_List_Base',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_BASE)
        );
    }

    public function testUnspecifiedQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array('queries' => array(Horde_Kolab_Storage_List_Tools::QUERY_BASE))
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_List_Base', $tools->getQuery()
        );
    }

    public function testCachedListQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'queries' => array(
                    'list' => array(
                        Horde_Kolab_Storage_List_Tools::QUERY_BASE => array(
                            'defaults_bail' => true,
                            'cache' => true
                        )
                    )
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_List_Cache',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_BASE)
        );
    }

    public function testAclQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'queries' => array(
                    'list' => array(
                        Horde_Kolab_Storage_List_Tools::QUERY_BASE => true,
                        Horde_Kolab_Storage_List_Tools::QUERY_ACL => true
                    )
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_Acl_Base',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_ACL)
        );
    }

    public function testCachedAclQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'queries' => array(
                    'list' => array(
                        Horde_Kolab_Storage_List_Tools::QUERY_ACL => array(
                            'cache' => true
                        )
                    )
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_Acl_Cache',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_ACL)
        );
    }

    public function testShareQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'queries' => array(
                    'list' => array(
                        Horde_Kolab_Storage_List_Tools::QUERY_SHARE => true
                    )
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_Share_Base',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_SHARE)
        );
    }

    public function testCachedShareQuery()
    {
        $tools = new Horde_Kolab_Storage_List_Tools(
            $this->_getDriver(),
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            array(
                'queries' => array(
                    'list' => array(
                        Horde_Kolab_Storage_List_Tools::QUERY_SHARE => array(
                            'cache' => true
                        )
                    )
                )
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_Share_Cache',
            $tools->getQuery(Horde_Kolab_Storage_List_Tools::QUERY_SHARE)
        );
    }

    public function testGetId()
    {
        $driver = $this->_getDriver();
        $driver->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('ID'));
        $tools = new Horde_Kolab_Storage_List_Tools(
            $driver,
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger')
        );
        $this->assertEquals('ID', $tools->getId());
    }

    public function testGetNamespace()
    {
        $driver = $this->_getDriver();
        $driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue('NAMESPACE'));
        $tools = new Horde_Kolab_Storage_List_Tools(
            $driver,
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger')
        );
        $this->assertEquals('NAMESPACE', $tools->getNamespace());
    }

    private function _getDriver()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('getParameters')
            ->will(
                $this->returnValue(
                    array('host' => 'a', 'port' => 1, 'user' => 'b')
                )
            );
        return $driver;
    }
}