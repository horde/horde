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
 * Tests for the HashTable memcache storage driver.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    HashTable
 * @subpackage UnitTests
 */
class Horde_HashTable_Driver_MemcacheTest extends Horde_HashTable_Driver_TestBase
{
    public static function setUpBeforeClass()
    {
        if (extension_loaded('memcache') &&
            ($config = self::getConfig('HASHTABLE_MEMCACHE_TEST_CONFIG', __DIR__ . '/..')) &&
            isset($config['hashtable']['memcache'])) {
            $memcache = new Horde_Memcache(
                array_merge(
                    $config['hashtable']['memcache'],
                    array('prefix' => 'horde_hashtable_memcachetest')
                )
            );
            self::$_driver = new Horde_HashTable_Memcache(array('memcache' => $memcache));
        } else {
            self::$_skip = 'Memcache or configuration not available.';
        }
    }
}
