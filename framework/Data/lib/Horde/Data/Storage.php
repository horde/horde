<?php
/**
 * Abstract class that implements the temporary storage backend.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Data
 */
interface Horde_Data_Storage
{
    /**
     * Retrieve the data for a key.
     *
     * @param string $key  Key.
     *
     * @return mixed  Data value.
     */
    public function get($key);

    /**
     * Set the data for a key.
     *
     * @param string $key   Key.
     * @param mixed $value  Value. If null, clears the key value.
     */
    public function set($key, $value = null);

    /**
     * Does the key exist?
     *
     * @param string $key  Key.
     *
     * @return boolean  Does the key exist?
     */
    public function exists($key);

    /**
     * Clear all stored data.
     */
    public function clear();

}
