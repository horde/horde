<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  HashTable
 */

/**
 * Implementation of HashTable that stores nothing.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 */
class Horde_HashTable_Null
extends Horde_HashTable_Base
implements Horde_HashTable_Lock
{
    /**
     */
    protected function _delete($keys)
    {
        return true;
    }

    /**
     */
    protected function _exists($keys)
    {
        return false;
    }

    /**
     */
    protected function _get($keys)
    {
        return array_fill_keys($keys, false);
    }

    /**
     */
    protected function _set($key, $val, $opts)
    {
        return empty($opts['replace']);
    }

    /**
     */
    public function clear()
    {
    }

    /**
     */
    public function lock($key)
    {
    }

    /**
     */
    public function unlock($key)
    {
    }

}
