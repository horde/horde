<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package   Autoloader_Cache
 */

/**
 * Eaccelerator based autoloader caching backend.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Gunnar Wrobel <wrobel@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package   Autoloader_Cache
 */
class Horde_Autoloader_Cache_Backend_Eaccelerator
extends Horde_Autoloader_Cache_Backend
{
    /**
     */
    static public function isSupported()
    {
        return extension_loaded('eaccelerator');
    }

    /**
     */
    protected function _store($data)
    {
        eaccelerator_put($this->_key, $data);
    }

    /**
     */
    protected function _fetch()
    {
        return eaccelerator_get($this->_key);
    }

    /**
     */
    public function prune()
    {
        /* Undocumented, unknown return value. */
        eaccelerator_rm($this->_key);
        return true;
    }

}
