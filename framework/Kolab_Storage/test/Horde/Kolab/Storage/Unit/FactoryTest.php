<?php
/**
 * Test the factory.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_FactoryTest
extends Horde_Kolab_Storage_TestCase
{
    public function testCreationFromParams()
    {
        $factory = new Horde_Kolab_Storage_Factory(array('driver' => 'mock'));
        $this->assertInstanceOf(
            'Horde_Kolab_Storage',
            $factory->create()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array()
        );
        $factory->createDriver();
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testInvalidDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array('driver' => 'something')
        );
        $factory->createDriver();
    }

    public function testMockDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array('driver' => 'mock')
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Driver_Mock',
            $factory->createDriver()
        );
    }

    public function testMockParser()
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array('driver' => 'mock')
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Data_Parser',
            $factory->createDriver()->getParser()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testInvalidNamespace()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $factory->createNamespace(
            'undefined', 'test'
        );
    }

    public function testFixedNamespace()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace_Fixed',
            $factory->createNamespace(
                'fixed', 'test'
            )
        );
    }

    public function testLogDecoration()
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'logger' => $this->getMockLogger()
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Decorator_Log',
            $factory->create()->getList()
        );
    }

    public function testCacheDecoration()
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'params' => array(
                    'username' => 'test',
                    'host' => 'localhost',
                    'port' => 143,
                ),
                'cache' => new Horde_Cache(new Horde_Cache_Storage_Mock())
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Decorator_Cache',
            $factory->create()->getList()
        );
    }

    public function testTimerDecoration()
    {
        $logger = $this->getMockLogger();
        $factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'logger' => $logger,
                'timelog' => $logger,
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Driver_Decorator_Timer',
            $factory->createDriver()
        );
    }

    public function testCreateTypeReturnsType()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Type',
            $factory->createFolderType(
                'event'
            )
        );
    }

    public function testCacheInstance()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $cache = new Horde_Cache(new Horde_Cache_Storage_Mock());
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Cache', $factory->createCache($cache)
        );
    }

    public function testCacheFilebased()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Cache', $factory->createCache(array())
        );
    }

    public function testHistory()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_History', $factory->createHistory('test')
        );
    }

    public function testHistoryInject()
    {
        $history = new Horde_History_Mock('test');
        $factory = new Horde_Kolab_Storage_Factory(
            array('history' => $history)
        );
        $this->assertSame(
            $history, $factory->createHistory('test')
        );
    }

    public function testFolder()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder',
            $factory->createFolder(
                $this->getMock('Horde_Kolab_Storage_List'),
                'INBOX'
            )
        );
    }

    public function testFormat()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format',
            $factory->createFormat('Xml', 'contact', 1)
        );
    }

    public function testSameFormat()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertSame(
            $factory->createFormat('Xml', 'contact', 1),
            $factory->createFormat('Xml', 'contact', 1)
        );
    }

    public function testFormatParameters()
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array('format' => array('timelog' => true))
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Decorator_Timed',
            $factory->createFormat('Xml', 'contact', 1)
        );
    }

}
