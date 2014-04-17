<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader_Cache
 */

/**
 * Bootstrap cache storage driver.
 *
 * Used for caching autoloader data before the full autoloader environment is
 * setup. Transparently compresses the data if possible also.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader_Cache
 * @since     2.1.0
 */
class Horde_Autoloader_Cache_Bootstrap
{
    /* Cache types. */
    const APC = 1;
    const XCACHE = 2;
    const EACCELERATOR = 4;
    const TEMPFILE = 8;

    /* Compress types. */
    const LZ4 = 16;
    const LZF = 32;

    /* Serialize types. */
    const MSGPACK = 64;
    const JSON = 128;

    /**
     * The storage parameters mask.
     *
     * @var array
     */
    protected $_mask = 0;

    /**
     * Temporary directory.
     *
     * @var string
     */
    protected $_tempdir;

    /**
     * Constructor.
     *
     * @param array $opts  Options:
     *   - tempdir: (string) Use this path as the temporary directory.
     */
    public function __construct(array $opts = array())
    {
        if (extension_loaded('apc')) {
            $this->_mask |= self::APC;
        } elseif (extension_loaded('xcache')) {
            $this->_mask |= self::XCACHE;
        } elseif (extension_loaded('eaccelerator')) {
            $this->_mask |= self::EACCELERATOR;
        } else{
            $tempdir = isset($opts['tempdir'])
                ? $opts['tempdir']
                : sys_get_temp_dir();
            if (is_readable($tempdir)) {
                $this->_tempdir = $tempdir;
                $this->_mask |= self::TEMPFILE;
            }
        }

        if (extension_loaded('horde_lz4')) {
            $this->_mask |= self::LZ4;
        } elseif (extension_loaded('lzf')) {
            $this->_mask |= self::LZF;
        }

        $this->_mask |= extension_loaded('msgpack')
            ? self::MSGPACK
            : self::JSON;
    }

    /**
     * Return cached data.
     *
     * @param string $key  Cache key.
     *
     * @return mixed  Cache data, or false if not found.
     */
    public function get($key)
    {
        if ($this->_mask & self::APC) {
            $data = apc_fetch($key);
        } elseif ($this->_mask & self::XCACHE) {
            $data = xcache_get($key);
        } elseif ($this->_mask & self::EACCELERATOR) {
            $data = eaccelerator_get($key);
        } elseif ($this->_mask & self::TEMPFILE) {
            $data = @file_get_contents($this->_tempdir . '/' . $key);
            if ($data === false) {
                unlink($this->_tempdir . '/' . $key);
            }
        } else {
            return false;
        }

        if ($data) {
            if ($this->_mask & self::LZ4) {
                $data = @horde_lz4_uncompress($data);
            } elseif ($this->_mask & self::LZF) {
                $data = @lzf_decompress($data);
            }
        }

        return ($data === false)
            ? false
            : ($this->_mask & self::MSGPACK)
                  ? msgpack_unpack($data)
                  : @json_decode($data, true);
    }

    /**
     * Set cached data.
     *
     * @param string $key  Cache key.
     * @param mixed $data  Data to store.
     *
     * @return boolean  True on success, false on failure.
     */
    public function set($key, $data)
    {
        $data = ($this->_mask & self::MSGPACK)
            ? msgpack_pack($data)
            : json_encode($data);

        if ($this->_mask & self::LZ4) {
            $data = @horde_lz4_compress($data);
        } elseif ($this->_mask & self::LZF) {
            $data = lzf_compress($data);
        }

        if ($this->_mask & self::APC) {
            return apc_store($key, $data);
        } elseif ($this->_mask & self::XCACHE) {
            return xcache_set($key, $data);
        } elseif ($this->_mask & self::EACCELERATOR) {
            eaccelerator_put($key, $data);
            return true;
        } elseif ($this->_mask & self::TEMPFILE) {
            return file_put_contents($this->_tempdir . '/' . $key, $data);
        }

        return false;
    }

    /**
     * Delete a key.
     *
     * @param string $key  Cache key.
     */
    public function delete($key)
    {
        if ($this->_mask & self::APC) {
            apc_delete($key);
        } elseif ($this->_mask & self::XCACHE) {
            xcache_unset($key);
        } elseif ($this->_mask & self::EACCELERATOR) {
            eaccelerator_rm($key);
        } elseif ($this->_mask & self::TEMPFILE) {
            @unlink($this->_tempdir . '/' . $key);
        }
    }

}
