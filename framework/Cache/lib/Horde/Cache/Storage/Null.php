<?php
/**
 * Copyright 2006-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2006-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */

/**
 * Null cache storage driver.
 *
 * @author    Duck <duck@obala.net>
 * @category  Horde
 * @copyright 2006-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */
class Horde_Cache_Storage_Null extends Horde_Cache_Storage_Base
{
    /**
     */
    public function get($key, $lifetime = 0)
    {
        return false;
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        return false;
    }

    /**
     */
    public function expire($key)
    {
        return false;
    }

    /**
     */
    public function clear()
    {
    }

}
