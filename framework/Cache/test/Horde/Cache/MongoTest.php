<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */

/**
 * This class tests the MongoDB backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_MongoTest extends Horde_Cache_TestBase
{
    protected function _getCache($params = array())
    {
        if (!class_exists('Horde_Mongo_Client')) {
            $this->reason = 'Horde_Mongo not installed';
            return;
        }
        if (!extension_loaded('mongo')) {
            $this->reason = 'Mongo extension not loaded';
            return;
        }
        if (!($config = self::getConfig('CACHE_MONGO_TEST_CONFIG', __DIR__)) ||
            !isset($config['cache']['mongo']['hostspec'])) {
        }
        $factory = new Horde_Test_Factory_Mongo();
        $this->mongo = $factory->create(array(
            'config' => $config['cache']['mongo']['hostspec'],
            'dbname' => 'horde_cache_test'
        ));
        return new Horde_Cache(
            new Horde_Cache_Storage_Mongo(array(
                'mongo_db' => $this->mongo,
                'collection' => 'horde_cache_test'
            ))
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        if (!empty($this->mongo)) {
            $this->mongo->selectDB(null)->drop();
        }
    }
}
