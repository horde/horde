<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    HashTable
 * @subpackage UnitTests
 */

/**
 * Tests for the HashTable memcached storage driver.
 *
 * @author     Carlos Pires <acmpires@sapo.pt>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    HashTable
 * @subpackage UnitTests
 */
class Horde_HashTable_Driver_MemcachedTest extends Horde_HashTable_Driver_TestBase
{
    public static function setUpBeforeClass()
    {
        if (extension_loaded('memcached') &&
            ($config = self::getConfig('HASHTABLE_MEMCACHE_TEST_CONFIG', __DIR__ . '/..')) &&
            isset($config['hashtable']['memcached'])) {
            $memcache = new Horde_Memcache(
                array_merge(
                    $config['hashtable']['memcached'],
                    array('prefix' => 'horde_hashtable_memcachedtest')
                )
            );
            self::$_driver = new Horde_HashTable_Memcached(array('memcached' => $memcached));
        } else {
            self::$_skip = 'Memcached or configuration not available.';
        }
    }
}
