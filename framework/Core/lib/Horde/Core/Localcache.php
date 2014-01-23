<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Internal class used to store system-level cache data on the local
 * filesystem (as opposed to Horde_Cache, which is meant for storing
 * user-level cache data).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Core_Localcache
{
    /**
     * Local temporary directory.
     *
     * @var string
     */
    private $_tempdir = null;

    /**
     * Store data.
     *
     * @param string $key   Cache key.
     * @param mixed $value  Cache value.
     *
     * @return boolean  True if the data doesn't exist and was successfully
     *                  saved.
     */
    public function set($key, $value)
    {
        $fname = $this->_getFilename($key);

        return file_exists($fname)
            ? false
            : (bool)file_put_contents(
                  $fname,
                  $GLOBALS['injector']->getInstance('Horde_Pack')->pack($value),
                  LOCK_EX
              );
    }

    /**
     * Retrieve data.
     *
     * @param string $key  Cache key.
     *
     * @return mixed  Null if cache data is not available, or the cache data.
     */
    public function get($key)
    {
        $fname = $this->_getFilename($key);

        if (is_readable($fname)) {
            try {
                return $GLOBALS['injector']->getInstance('Horde_Pack')->unpack(
                    file_get_contents($fname)
                );
            } catch (Horde_Pack_Exception $e) {}

            /* On error, purge existing cache. */
            $this->purge($key);
        }

        return null;
    }

    /**
     * Purge data.
     *
     * @param string $key  Cache key.
     */
    public function purge($key)
    {
        unlink($this->_getFilename($key));
    }

    /**
     * Return the filename of the cache data.
     *
     * @param string $key  Cache key.
     *
     * @return string  Filename.
     */
    private function _getFilename($key)
    {
        if (is_null($this->_tempdir)) {
            $this->_tempdir = Horde::getTempDir();
        }

        return $this->_tempdir . '/' . hash('sha1', $key);
    }

}
