<?php
/**
 * Test the cached data handler.
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
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the cached data handler.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Data_Decorator_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testDefaultType()
    {
        $this->assertEquals(
            'event',
            $this->_getDataCache()
            ->getType()
        );
    }

    public function testStamp()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Stamp',
            $this->_getDataCache()->getStamp()
        );

    }

    public function testFetchPart()
    {
        $part = stream_get_contents(
            $this->_getDataCache()
            ->fetchPart(1, '2')
        );
        $this->assertContains('<event', $part);
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingObjects()
    {
        $this->getMockDataCache()->getObjects();
    }

    public function testSynchronize()
    {
        $this->_getDataCache()->synchronize();
    }

    public function testSaveAfterCompleteSync()
    {
        $mock = $this->getMock('Horde_Kolab_Storage_Cache_Data', array(), array(), '', false, false);
        $mock->expects($this->once())
            ->method('isInitialized')
            ->will($this->returnValue(false));
        $mock->expects($this->once())
            ->method('save');
        $this->_getCacheDecorator($mock);
    }

    public function testFetch()
    {
        $objects = $this->_getDataCache()
            ->fetch(array(1, 2, 4));
        $this->assertEquals('libkcal-543769073.139', $objects[4]['uid']);
    }

    public function testDataQueriable()
    {
        $data = $this->_getDataCache();
        $this->assertTrue($data instanceOf Horde_Kolab_Storage_Queriable);
    }

    public function testGetObjects()
    {
        $this->assertType(
            'array',
            $this->_getDataCache()
            ->getObjects()
        );
    }

    public function testObjects()
    {
        $objects = $this->_getDataCache()
            ->getObjects();
        $this->assertEquals(
            'libkcal-543769073.139',
            $objects['libkcal-543769073.139']['uid']
        );
    }

    public function testGetObjectIds()
    {
        $this->assertType(
            'array',
            $this->_getDataCache()->getObjectIds()
        );
    }

    public function testObjectIds()
    {
        $this->assertEquals(
            array('libkcal-543769073.139'),
            $this->_getDataCache()->getObjectIds()
        );
    }

    public function testBackendId()
    {
        $this->assertEquals(
            '4',
            $this->_getDataCache()
            ->getBackendId('libkcal-543769073.139')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingBackendId()
    {
        $this->_getDataCache()
            ->getBackendId('NOSUCHOBJECT');
    }

    public function testExists()
    {
        $this->assertTrue(
            $this->_getDataCache()
            ->objectIdExists('libkcal-543769073.139')
        );
    }

    public function testDoesNotExist()
    {
        $this->assertFalse(
            $this->_getDataCache()
            ->objectIdExists('NOSUCHOBJECT')
        );
    }

    public function testGetObject()
    {
        $object = $this->_getDataCache()
            ->getObject('libkcal-543769073.139');
        $this->assertEquals(
            'libkcal-543769073.139',
            $object['uid']
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingObject()
    {
        $object = $this->_getDataCache()
            ->getObject('NOSUCHOBJECT');
    }

    private function _getDataCache()
    {
        return $this->_getCacheDecorator(
            $this->data_cache = $this->getMockDataCache()
        );
    }

    private function _getCacheDecorator(Horde_Kolab_Storage_Cache_Data $cache)
    {
        $this->storage = $this->getMessageStorage();
        $cache = new Horde_Kolab_Storage_Data_Decorator_Cache(
            $this->storage->getData('INBOX/Calendar'),
            $cache
        );
        $cache->synchronize();
        return $cache;
    }
}
