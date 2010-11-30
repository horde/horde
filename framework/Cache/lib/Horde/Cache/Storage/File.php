<?php
/**
 * This class provides cache storage in the filesystem.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Cache
 */
class Horde_Cache_Storage_File extends Horde_Cache_Storage_Base
{
    /* Location of the garbage collection data file. */
    const GC_FILE = 'horde_cache_gc';

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
     * Constructor.
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
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'prefix' => 'cache_',
            'sub' => 0
        ), $params);

        $this->_dir = (isset($params['dir']) && @is_dir($params['dir']))
            ? $params['dir']
            : Horde_Util::getTempDir();

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

        $filename = $this->_dir . '/' . self::GC_FILE;
        $excepts = array();

        if (is_readable($filename)) {
            $gc_file = file($filename, FILE_IGNORE_NEW_LINES);
            reset($gc_file);
            next($gc_file);
            while (list(,$data) = each($gc_file)) {
                $parts = explode("\t", $data, 2);
                $excepts[$parts[0]] = $parts[1];
            }
        }

        try {
            $it = empty($this->_params['sub'])
                ? new DirectoryIterator($this->_dir)
                : new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_dir), RecursiveIteratorIterator::CHILD_FIRST);
        } catch (UnexpectedValueException $e) {
            return;
        }

        $c_time = time();

        foreach ($it as $val) {
            if (!$val->isDir() &&
                ($fname = $val->getFilename()) &&
                (strpos($fname, $this->_params['prefix']) === 0)) {
                $d_time = isset($excepts[$fname])
                    ? $excepts[$fname]
                    : $this->_params['lifetime'];

                if (!empty($d_time) &&
                    (($c_time - $d_time) > filemtime($val->getPathname()))) {
                    @unlink($val->getPathname());
                    unset($excepts[$fname]);
                }
            }
        }

        if ($fp = @fopen($filename, 'w')) {
            foreach ($excepts as $key => $val) {
                fwrite($fp, $key . "\t" . $val . "\n");
            }
            fclose($fp);
        }
    }

    /**
     */
    public function get($key, $lifetime = 0)
    {
        if (!$this->exists($key, $lifetime)) {
            /* Nothing cached, return failure. */
            return false;
        }

        $filename = $this->_keyToFile($key);
        $size = filesize($filename);

        return $size
            ? @file_get_contents($filename)
            : '';
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
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

        if (($lifetime != $this->_params['lifetime']) &&
            ($fp = @fopen($this->_dir . '/horde_cache_gc', 'a'))) {
            // This may result in duplicate entries in horde_cache_gc, but we
            // will take care of these whenever we do GC and this is quicker
            // than having to check every time we access the file.
            fwrite($fp, $filename . "\t" . (empty($lifetime) ? 0 : time() + $lifetime) . "\n");
            fclose($fp);
        }
    }

    /**
     */
    public function exists($key, $lifetime = 0)
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
     */
    public function expire($key)
    {
        $filename = $this->_keyToFile($key);
        return @unlink($filename);
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
            $md5 = hash('md5', $key);
            $sub = '';

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

}
