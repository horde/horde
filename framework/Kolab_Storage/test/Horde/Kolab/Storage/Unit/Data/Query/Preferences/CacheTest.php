<?php
/**
 * Test the handling of the cached preference data query.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the handling of the cached preference data query.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Data_Query_Preferences_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testNumberOfObjects()
    {
        $storage = $this->_getPreferences();
        $data = $storage->getData('INBOX/Preferences');
        $data->synchronize();
        $this->assertEquals(2, count($data->getObjects()));
    }

    public function testHordeApplication()
    {
        $prefs = $this->_getDataQuery()->getApplicationPreferences('horde');
        $this->assertEquals(
            '20080626155721.771268tms63o0rs4@devmail.example.com',
            $prefs['uid']
        );
    }

    public function testCaching()
    {
        $data = $this->_getData();
        $query = $this->_getQuery($data);
        $prefs = $query->getApplicationPreferences('horde');
        $this->assertEquals(
            '20080626155721.771268tms63o0rs4@devmail.example.com',
            $prefs['uid']
        );
    }

    private function _getData()
    {
        return $this->_getPreferences()->getData('INBOX/Preferences');
    }

    private function _getDataQuery()
    {
        return $this->_getData()
            ->getQuery(Horde_Kolab_Storage_Data::QUERY_PREFS);
    }

    private function _getQuery($data)
    {
        $cache = new Horde_Kolab_Storage_Cache($this->_cache);
        $data_cache = $cache->getDataCache($data->getIdParameters());
        $params = array('cache' => $data_cache);
        return new Horde_Kolab_Storage_Data_Query_Preferences_Cache(
            $data,
            $params
        );
    }

    private function _getPreferences()
    {
        $this->_cache = new Horde_Cache(new Horde_Cache_Storage_Mock());
        return $this->getDataStorage(
            $this->getDataAccount(
                array(
                    'user/test/Preferences' => array(
                        't' => 'h-prefs.default',
                        'm' => array(
                            1 => array('file' => __DIR__ . '/../../../../fixtures/preferences.1'),
                            2 => array('file' => __DIR__ . '/../../../../fixtures/preferences.2'),
                        ),
                    )
                )
            ),
            array(
                'cache' => $this->_cache,
                'queryset' => array('data' => array('queryset' => 'horde')),
            )
        );
    }
}