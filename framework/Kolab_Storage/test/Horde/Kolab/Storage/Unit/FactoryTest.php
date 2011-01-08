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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_FactoryTest
extends Horde_Kolab_Storage_TestCase
{
    public function testCreation()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertType(
            'Horde_Kolab_Storage_Base',
            $factory->create(
                new Horde_Kolab_Storage_Driver_Mock(
                    new Horde_Kolab_Storage_Factory()
                )
            )
        );
    }

    public function testCreationFromParams()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertType(
            'Horde_Kolab_Storage_Base',
            $factory->createFromParams(array('driver' => 'mock'))
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $factory->createDriverFromParams(array());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testInvalidDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $factory->createDriverFromParams(array('driver' => 'something'));
    }

    public function testMockDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertType(
            'Horde_Kolab_Storage_Driver_Mock',
            $factory->createDriverFromParams(
                array('driver' => 'mock')
            )
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
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Decorator_Log',
            $factory->createFromParams(
                array(
                    'driver' => 'mock',
                    'logger' => $this->getMockLogger()
                )
            )
        );
    }

    public function testCacheDecoration()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Decorator_Cache',
            $factory->createFromParams(
                array(
                    'driver' => 'mock',
                    'cache' => array('')
                )
            )
        );
    }

    public function testTimerDecoration()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $logger = $this->getMockLogger();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Driver_Decorator_Timer',
            $factory->createDriverFromParams(
                array(
                    'driver' => 'mock',
                    'logger' => $logger,
                    'timelog' => $logger,
                )
            )
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

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testCreateQueryForUnsupported()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $factory->createListQuery('NO_SUCH_QUERY', $list);
    }

    public function testQueryReturnsQuery()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Query',
            $factory->createListQuery('Base', $list)
        );
    }

    public function testFactoryInjection()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Query',
            $factory->createListQuery('Base', $list)
        );
    }

    public function testQueryStub()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Query',
            $factory->createListQuery(
                'Horde_Kolab_Storage_Stub_FactoryQuery',
                $list
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
}
