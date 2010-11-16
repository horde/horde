<?php
/**
 * This class provides a null cache storage driver.
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Cache
 */
class Horde_Cache_Storage_Null extends Horde_Cache_Storage_Base
{
    /**
     */
    public function get($key, $lifetime)
    {
        return false;
    }

    /**
     */
    public function set($key, $data, $lifetime)
    {
    }

    /**
     */
    public function exists($key, $lifetime)
    {
        return false;
    }

    /**
     */
    public function expire($key)
    {
        return false;
    }

}
