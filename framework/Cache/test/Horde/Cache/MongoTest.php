<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
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
        if (!extension_loaded('mongo') && !extension_loaded('mongodb')) {
            $this->reason = 'Mongo/Mongodb extensions not loaded';
            return;
        }
        if (!class_exists('Horde_Mongo_Client')) {
            $this->reason = 'Horde_Mongo not installed';
            return;
        }
        if (!($config = self::getConfig('CACHE_MONGO_TEST_CONFIG', __DIR__)) ||
            !isset($config['cache']['mongo']['hostspec'])) {
            $this->reason = 'Mongo configuration not available';
            return;
        }
        $factory = new Horde_Test_Factory_Mongo();
        $this->mongo = $factory->create(array(
            'config' => $config['cache']['mongo']['hostspec'],
            'dbname' => 'horde_cache_test'
        ));
        if (!$this->mongo) {
            $this->reason = 'MongoDB not available.';
            return;
        }
        $storage = new Horde_Cache_Storage_Mongo(array(
            'mongo_db' => $this->mongo,
            'collection' => 'horde_cache_test'
        ));
        $storage->setLogger(new Horde_Log_Logger(new Horde_Log_Handler_Cli()));
        return new Horde_Cache($storage);
    }

    public function tearDown()
    {
        parent::tearDown();
        if (!empty($this->mongo)) {
            $this->mongo->selectDB(null)->drop();
        }
    }
}
