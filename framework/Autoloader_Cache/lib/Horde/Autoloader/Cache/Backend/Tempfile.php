<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package   Autoloader_Cache
 */

/**
 * Temporary file based autoloader caching backend.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Gunnar Wrobel <wrobel@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package   Autoloader_Cache
 */
class Horde_Autoloader_Cache_Backend_Tempfile
extends Horde_Autoloader_Cache_Backend
{
    /**
     */
    public function __construct($key)
    {
        $this->_key = sys_get_temp_dir() . '/' . hash('md5', $key);
    }

    /**
     */
    static public function isSupported()
    {
        return is_readable(sys_get_temp_dir());
    }

    /**
     */
    protected function _store($data)
    {
        file_put_contents($this->_key, $data);
    }

    /**
     */
    protected function _fetch()
    {
        return @file_get_contents($this->_key);
    }

    /**
     */
    public function prune()
    {
        return file_exists($this->_key)
            ? unlink($this->_key)
            : true;
    }

}
