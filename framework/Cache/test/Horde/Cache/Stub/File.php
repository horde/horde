<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2016-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */

/**
 * Stub for cache storage in the filesystem.
 *
 * @author    Jan Schneider <slusarz@horde.org>
 * @category  Horde
 * @copyright 2016-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */
class Horde_Cache_Stub_File extends Horde_Cache_Storage_File
{
    /**
     * Enforces garbage collection.
     */
    public function gc()
    {
        $this->_gc();
    }
}
