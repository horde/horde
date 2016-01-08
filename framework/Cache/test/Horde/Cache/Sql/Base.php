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
 * This class tests the File backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_FileTest extends Horde_Cache_TestBase
{
    protected function _getCache($params = array())
    {
        $this->dir = sys_get_temp_dir() . '/horde_cache_test';
        mkdir($this->dir);
        $cache = new Horde_Cache(
            new Horde_Cache_Storage_File(array_merge(
                array(
                    'dir'   => $this->dir,
                    'no_gc' => true,
                ),
                $params
            ))
        );
        return $cache;
    }

    public function tearDown()
    {
        parent::tearDown();
        system('rm -r ' . $this->dir);
    }
}
