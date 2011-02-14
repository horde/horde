<?php
/**
 * Test the data cache.
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
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the data cache.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Cache_DataTest
extends Horde_Kolab_Storage_TestCase
{
    public function testDataId()
    {
        $this->assertEquals('test', $this->getMockDataCache()->getDataId());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingDataId()
    {
        $cache = new Horde_Kolab_Storage_Cache_Data($this->getMockCache());
        $cache->getDataId();
    }

    public function testNotInitialized()
    {
        $this->assertFalse($this->getMockDataCache()->isInitialized());
    }

    public function testInvalidVersion()
    {
        $cache = $this->getMockCache();
        $cache->storeData(
            'test', serialize(array('S' => time(), 'V' => '0'))
        );
        $this->assertFalse($this->getMockDataCache($cache)->isInitialized());
    }

    public function testMissingSync()
    {
        $cache = $this->getMockCache();
        $cache->storeData(
            'test', serialize(
                array('V' => Horde_Kolab_Storage_Cache_Data::VERSION)
            )
        );
        $this->assertFalse($this->getMockDataCache($cache)->isInitialized());
    }

    public function testGetObjects()
    {
        $this->_getSyncedCache()->getObjects();
    }

    public function testGetObjectsEmpty()
    {
        $this->assertEquals(
            array(),
            $this->_getSyncedCache()->getObjects()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingObjects()
    {
        $this->getMockDataCache()->getObjects();
    }

    public function testGetObjectsOne()
    {
        $this->assertEquals(
            array('test' => array('uid' => 'test')),
            $this->_getSyncedCacheWithData()->getObjects()
        );
    }

    public function testGetObjectsTwo()
    {
        $this->assertEquals(
            array('test', 'test2'),
            array_keys($this->_getSyncedCacheWithMoreData()->getObjects())
        );
    }

    public function testGetObjectToBackend()
    {
        $this->_getSyncedCache()->getObjectToBackend();
    }

    public function testGetObjectToBackendEmpty()
    {
        $this->assertEquals(
            array(),
            $this->_getSyncedCache()->getObjectToBackend()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingObjectToBackend()
    {
        $this->getMockDataCache()->getObjectToBackend();
    }

    public function testGetObjectToBackendOne()
    {
        $this->assertEquals(
            array('test' => '1'),
            $this->_getSyncedCacheWithData()->getObjectToBackend()
        );
    }

    public function testGetObjectToBackendTwo()
    {
        $this->assertEquals(
            array('test' => '1', 'test2' => '2'),
            $this->_getSyncedCacheWithMoreData()->getObjectToBackend()
        );
    }

    public function testGetBackendToObject()
    {
        $this->_getSyncedCache()->getBackendToObject();
    }

    public function testGetBackendToObjectEmpty()
    {
        $this->assertEquals(
            array(),
            $this->_getSyncedCache()->getBackendToObject()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingBackendToObject()
    {
        $this->getMockDataCache()->getBackendToObject();
    }

    public function testGetBackendToObjectOne()
    {
        $this->assertEquals(
            array('1' => 'test'),
            $this->_getSyncedCacheWithData()->getBackendToObject()
        );
    }

    public function testGetBackendToObjectTwo()
    {
        $this->assertEquals(
            array('1'=> 'test', '2' => 'test2'),
            $this->_getSyncedCacheWithMoreData()->getBackendToObject()
        );
    }

    public function testGetStamp()
    {
        $this->_getSyncedCache()->getStamp();
    }

    public function testGetStampEmpty()
    {
        $this->assertType(
            'string',
            $this->_getSyncedCache()->getStamp()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingStamp()
    {
        $this->getMockDataCache()->getStamp();
    }

    public function testGetStampOne()
    {
        $this->assertEquals(
            'C:37:"Horde_Kolab_Storage_Folder_Stamp_Uids":30:{a:2:{i:0;s:1:"a";i:1;s:1:"b";}}',
            $this->_getSyncedCacheWithData()->getStamp()
        );
    }

    public function testGetStampTwo()
    {
        $this->assertEquals(
            'C:37:"Horde_Kolab_Storage_Folder_Stamp_Uids":30:{a:2:{i:0;s:1:"c";i:1;s:1:"d";}}',
            $this->_getSyncedCacheWithMoreData()->getStamp()
        );
    }

    public function testGetVersion()
    {
        $this->_getSyncedCache()->getVersion();
    }

    public function testGetVersionEmpty()
    {
        $this->assertType(
            'string',
            $this->_getSyncedCache()->getVersion()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingVersion()
    {
        $this->getMockDataCache()->getVersion();
    }

    public function testGetVersionOne()
    {
        $this->assertEquals(
            '1',
            $this->_getSyncedCacheWithData()->getVersion()
        );
    }

    public function testGetVersionTwo()
    {
        $this->assertEquals(
            '2',
            $this->_getSyncedCacheWithMoreData()->getVersion()
        );
    }


    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testReset()
    {
        $cache = $this->_getSyncedCache();
        $cache->reset();
        $cache->getStamp();
    }

    public function testStoreBackendMapping()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array('1000' => array('uid' => 'OBJECTID')),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1'
        );
        $this->assertEquals(
            array('1000' => 'OBJECTID'), $cache->getBackendToObject()
        );
    }
    
    public function testStoreObjectMapping()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array('1000' => array('uid' => 'OBJECTID')),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1'
        );
        $this->assertEquals(
            array('OBJECTID' => '1000'), $cache->getObjectToBackend()
        );
    }
    
    public function testStoreObjects()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array('1000' => array('uid' => 'OBJECTID')),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1'
        );
        $this->assertEquals(
            array('OBJECTID' => array('uid' => 'OBJECTID')),
            $cache->getObjects()
        );     
    }
    
    public function testIgnoreNoUid()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array('1000' => array('test' => 'test')),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1'
        );
        $this->assertEquals(
            array('1000' => false), $cache->getBackendToObject()
        );
    }
    
    public function testIgnoreFalse()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array('1000' => false),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1'
        );
        $this->assertEquals(
            array('1000' => false), $cache->getBackendToObject()
        );
    }
    
    public function testLoadSave()
    {
        $cache = new Horde_Kolab_Storage_Cache(new Horde_Cache(new Horde_Cache_Storage_Mock()));
        $data_cache = new Horde_Kolab_Storage_Cache_Data(
            $cache
        );
        $data_cache->setDataId('test');
        $data_cache->store(
            array('1000' => array('uid' => 'OBJECTID')),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1'
        );
        $data_cache->save();
        $data_cache = new Horde_Kolab_Storage_Cache_Data(
            $cache
        );
        $data_cache->setDataId('test');
        $data_cache->store(
            array('1001' => false),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'c'),
            '1'
        );
        $data_cache->save();
        $data_cache = new Horde_Kolab_Storage_Cache_Data(
            $cache
        );
        $data_cache->setDataId('test');
        $this->assertEquals(
            array('OBJECTID' => array('uid' => 'OBJECTID')),
            $data_cache->getObjects()
        );     
    }

    public function testDeletion()
    {
        $this->assertEquals(
            array(),
            $this->_getWithDeletion()->getObjects()
        );     
    }
    
    public function testDeletionInObjectMapping()
    {
        $this->assertEquals(
            array(),
            $this->_getWithDeletion()->getObjectToBackend()
        );     
    }

    public function testDeletionInBackendMapping()
    {
        $this->assertEquals(
            array('1001' => false),
            $this->_getWithDeletion()->getBackendToObject()
        );     
    }

    public function testGetMissingAttachment()
    {
        $this->assertFalse($this->getMockDataCache()->getAttachment('100', '1'));
    }

    public function testGetAttachment()
    {
        $resource = fopen('php://temp', 'r+');
        $this->horde_cache = $this->getMockCache();
        $cache = $this->horde_cache->getDataCache(
            array(
                'host' => 'localhost',
                'port' => '143',
                'folder' => 'test',
                'type' => 'event',
                'owner' => 'someuser',
            )
        );
        $this->horde_cache->storeAttachment(
            $cache->getDataId(), '100', '1', $resource
        );
        $this->assertSame(
            $resource,
            $cache->getAttachment('100', '1')
        );
    }

    public function testGetStoredAttachment()
    {
        $resource = fopen('php://temp', 'r+');
        $this->assertSame(
            $resource,
            $this->_getSyncedCacheWithAttachment($resource)
            ->getAttachment('100', '1')
        );
    }

    public function testDeletedAttachment()
    {
        $resource = fopen('php://temp', 'r+');
        $cache = $this->_getSyncedCacheWithAttachment($resource);
        $cache->store(
            array(),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('c', 'd'),
            '2',
            array('100')
        );
        $this->assertFalse(
            $cache->getAttachment('100', '1')
        );
    }

    public function testGetAttachmentByName()
    {
        $this->assertEquals(
            array('1', '3'),
            array_keys(
                $this->_getSyncedCacheWithAttachment('Y')
                ->getAttachmentByName('100', 'test.txt')
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingAttachmentByName()
    {
        $this->_getSyncedCacheWithAttachment('Y')
            ->getAttachmentByName('100', 'dubidu.txt');
    }

    public function testGetAttachmentByType()
    {
        $this->assertEquals(
            array('1', '2', '3'),
            array_keys(
                $this->_getSyncedCacheWithAttachment('Y')
                ->getAttachmentByType('100', 'application/x-vnd.kolab.event')
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingAttachmentType()
    {
        $this->_getSyncedCacheWithAttachment('Y')
            ->getAttachmentByType('100', 'application/x-vnd.kolab.contact');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingAttachmentByType()
    {
        $this->_getSyncedCacheWithAttachment('Y')
            ->getAttachmentByType('200', 'application/x-vnd.kolab.event');
    }

    private function _getSyncedCache()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array(), new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'), '1'
        );
        return $cache;
    }

    private function _getSyncedCacheWithData()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array('1' => array('uid' => 'test')),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1'
        );
        return $cache;
    }

    private function _getSyncedCacheWithMoreData()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array(
                '1' => array('uid' => 'test'),
                '2' => array('uid' => 'test2')
            ),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('c', 'd'),
            '2'
        );
        return $cache;
    }

    private function _getSyncedCacheWithAttachment($resource)
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array(
                '100' => array(
                    'uid' => 'test',
                    '_attachments' => array(
                        '1' => array(
                            'name' => 'test.txt',
                            'type' => 'application/x-vnd.kolab.event',
                            'content' => $resource
                        ),
                        '2' => array(
                            'type' => 'application/x-vnd.kolab.event',
                            'content' => $resource
                        ),
                        '3' => array(
                            'name' => 'test.txt',
                            'type' => 'application/x-vnd.kolab.event',
                            'content' => $resource
                        ),
                        '4' => array(
                            'content' => $resource
                        )
                    )
                ),
            ),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('c', 'd'),
            '2'
        );
        return $cache;
    }

    private function _getWithDeletion()
    {
        $cache = $this->getMockDataCache();
        $cache->store(
            array('1000' => array('uid' => 'OBJECTID'), '1001' => false),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1'
        );
        $cache->store(
            array(),
            new Horde_Kolab_Storage_Folder_Stamp_Uids('a', 'b'),
            '1',
            array('1000')
        );
        return $cache;
    }
}
