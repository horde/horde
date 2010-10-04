<?php
/**
 * The Horde_Cache_File:: class provides a filesystem implementation of the
 * Horde caching system.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Cache
 */
class Horde_Cache_File extends Horde_Cache
{
    /**
     * The location of the temp directory.
     *
     * @var string
     */
    protected $_dir;

    /**
     * List of key to filename mappings.
     *
     * @var array
     */
    protected $_file = array();

    /**
     * Construct a new Horde_Cache_File object.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'dir' - (string) The base directory to store the cache files in.
     *         DEFAULT: System default
     * 'prefix' - (string) The filename prefix to use for the cache files.
     *            DEFAULT: 'cache_'
     * 'sub' - (integer) If non-zero, the number of subdirectories to create
     *         to store the file (i.e. PHP's session.save_path).
     *         DEFAULT: 0
     * </pre>
     */
    public function __construct($params = array())
    {
        $this->_dir = (!empty($params['dir']) && @is_dir($params['dir']))
            ? $params['dir']
            : Horde_Util::getTempDir();

        if (!isset($params['prefix'])) {
            $params['prefix'] = 'cache_';
        }

        if (!isset($params['sub'])) {
            $params['sub'] = 0;
        }

        parent::__construct($params);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        /* Only do garbage collection 0.1% of the time we create an object. */
        if (rand(0, 999) != 0) {
            return;
        }

        $filename = $this->_dir . '/horde_cache_gc';
        $excepts = array();

        if (file_exists($filename)) {
            $flags = defined('FILE_IGNORE_NEW_LINES')
                ? FILE_IGNORE_NEW_LINES
                : 0;

            $gc_file = file($filename, $flags);
            array_pop($gc_file);
            reset($gc_file);
            while (list(,$data) = each($gc_file)) {
                if (!$flags) {
                    $data = rtrim($data);
                }
                $parts = explode("\t", $data, 2);
                $excepts[$parts[0]] = $parts[1];
            }
        }

        try {
            $this->_gcDir($this->_dir, $excepts);
        } catch (Horde_Cache_Exception $e) {}

        $out = '';
        foreach ($excepts as $key => $val) {
            $out .= $key . "\t" . $val . "\n";
        }

        file_put_contents($filename, $out);
    }

    /**
     */
    protected function _get($key, $lifetime)
    {
        if (!$this->exists($key, $lifetime)) {
            /* Nothing cached, return failure. */
            return false;
        }

        $filename = $this->_keyToFile($key);
        $size = filesize($filename);
        if (!$size) {
            return '';
        }

        return @file_get_contents($filename);
    }

    /**
     */
    protected function _set($key, $data, $lifetime)
    {
        $filename = $this->_keyToFile($key, true);
        $tmp_file = Horde_Util::getTempFile('HordeCache', true, $this->_dir);
        if (isset($this->_params['umask'])) {
            chmod($tmp_file, 0666 & ~$this->_params['umask']);
        }

        if (file_put_contents($tmp_file, $data) === false) {
            throw new Horde_Cache_Exception('Cannot write to cache directory ' . $this->_dir);
        }

        @rename($tmp_file, $filename);

        $lifetime = $this->_getLifetime($lifetime);
        if ($lifetime != $this->_params['lifetime']) {
            // This may result in duplicate entries in horde_cache_gc, but we
            // will take care of these whenever we do GC and this is quicker
            // than having to check every time we access the file.
            $fp = @fopen($this->_dir . '/horde_cache_gc', 'a');
            if ($fp) {
                fwrite($fp, $filename . "\t" . (empty($lifetime) ? 0 : time() + $lifetime) . "\n");
                fclose($fp);
            }
        }
    }

    /**
     * Checks if a given key exists in the cache, valid for the given
     * lifetime. If it exists but is expired, delete the file.
     *
     * @param string $key        Cache key to check.
     * @param integer $lifetime  Lifetime of the key in seconds.
     *
     * @return boolean  Existence.
     */
    public function exists($key, $lifetime = 1)
    {
        $filename = $this->_keyToFile($key);

        /* Key exists in the cache */
        if (file_exists($filename)) {
            /* 0 means no expire.
             * Also, If the file was been created after the supplied value,
             * the data is valid (fresh). */
            if (($lifetime == 0) ||
                (time() - $lifetime <= filemtime($filename))) {
                return true;
            }

            @unlink($filename);
        }

        return false;
    }

    /**
     * Expire any existing data for the given key.
     *
     * @param string $key  Cache key to expire.
     *
     * @return boolean  Success or failure.
     */
    public function expire($key)
    {
        $filename = $this->_keyToFile($key);
        return @unlink($filename);
    }

    /**
     * Attempts to directly output a cached object.
     *
     * @param string $key        Object ID to query.
     * @param integer $lifetime  Lifetime of the object in seconds.
     *
     * @return boolean  True if output or false if no object was found.
     */
    public function output($key, $lifetime = 1)
    {
        if (!$this->exists($key, $lifetime)) {
            return false;
        }

        $filename = $this->_keyToFile($key);
        return @readfile($filename);
    }

    /**
     * Map a cache key to a unique filename.
     *
     * @param string $key     Cache key.
     * @param string $create  Create path if it doesn't exist?
     *
     * @return string  Fully qualified filename.
     */
    protected function _keyToFile($key, $create = false)
    {
        if ($create || !isset($this->_file[$key])) {
            $dir = $this->_dir . '/';
            $sub = '';
            $md5 = md5($key);
            if (!empty($this->_params['sub'])) {
                $max = min($this->_params['sub'], strlen($md5));
                for ($i = 0; $i < $max; $i++) {
                    $sub .= $md5[$i];
                    if ($create && !is_dir($dir . $sub)) {
                        if (!mkdir($dir . $sub)) {
                            $sub = '';
                            break;
                        }
                    }
                    $sub .= '/';
                }
            }
            $this->_file[$key] = $dir . $sub . $this->_params['prefix'] . $md5;
        }

        return $this->_file[$key];
    }

    /**
     * TODO
     *
     * @throws Horde_Cache_Exception
     */
    protected function _gcDir($dir, &$excepts)
    {
        $d = @dir($dir);
        if (!$d) {
            throw new Horde_Cache_Exception('Permission denied to ' . $dir);
        }

        $c_time = time();

        while (($entry = $d->read()) !== false) {
            $path = $dir . '/' . $entry;
            if (($entry == '.') || ($entry == '..')) {
                continue;
            }

            if (strpos($entry, $this->_params['prefix']) === 0) {
                $d_time = isset($excepts[$path]) ? $excepts[$path] : $this->_params['lifetime'];
                if (!empty($d_time) &&
                    (($c_time - $d_time) > filemtime($path))) {
                    @unlink($path);
                    unset($excepts[$path]);
                }
            } elseif (!empty($this->_params['sub']) && is_dir($path)) {
                $this->_gcDir($path, $excepts);
            }
        }
        $d->close();
    }

}
