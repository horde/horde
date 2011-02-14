<?php
/**
 * Test the cached data query.
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
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the cached data query.
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
class Horde_Kolab_Storage_Unit_Data_Query_Data_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testSynchronize()
    {
        $this->_getDataCache()->synchronize();
    }

    public function testGetObjects()
    {
        $this->assertType(
            'array',
            $this->_getDataCache()->getObjects()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMissingObjects()
    {
        $this->getMockDataCache()->getObjects();
    }

    public function testGetObjectIds()
    {
        $this->assertType(
            'array',
            $this->_getDataCache()->getObjectIds()
        );
    }

    public function testBackendId()
    {
        $this->assertEquals(
            '4',
            $this->_getDataCache()->getBackendId('libkcal-543769073.139')
        );
    }

    public function testExists()
    {
        $this->assertTrue(
            $this->_getDataCache()->objectIdExists('libkcal-543769073.139')
        );
    }

    public function testGetObject()
    {
        $object = $this->_getDataCache()->getObject('libkcal-543769073.139');
        $this->assertEquals(
            'libkcal-543769073.139',
            $object['uid']
        );
    }

    public function testObjectIds()
    {
        $this->assertEquals(
            array('libkcal-543769073.139'),
            $this->_getDataCache()->getObjectIds()
        );
    }

    public function testObjects()
    {
        $objects = $this->_getDataCache()->getObjects();
        $this->assertEquals(
            'libkcal-543769073.139',
            $objects['libkcal-543769073.139']['uid']
        );
    }

    private function _getDataCache()
    {
        $this->storage = $this->getMessageStorage();
        $this->data_cache = $this->getMockDataCache();
        $cache = new Horde_Kolab_Storage_Data_Query_Data_Cache(
            $this->storage->getData('INBOX/Calendar'),
            array(
                'cache' => $this->data_cache,
                'factory' => new Horde_Kolab_Storage_Factory()
            )
        );
        $cache->synchronize();
        return $cache;
    }
}
