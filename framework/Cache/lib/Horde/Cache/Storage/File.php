<?php
/**
 * Copyright 1999-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 1999-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */

/**
 * Cache storage in the filesystem.
 *
 * @author    Anil Madhavapeddy <anil@recoil.org>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */
class Horde_Cache_Storage_File extends Horde_Cache_Storage_Base
{
    /* Location of the garbage collection data file. */
    const GC_FILE = 'horde_cache_gc';

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
     *   - dir: (string) The base directory to store the cache files in.
     *          DEFAULT: System default
     *   - no_gc: (boolean) If true, don't perform garbage collection.
     *            DEFAULT: false
     *   - prefix: (string) The filename prefix to use for the cache files.
     *             DEFAULT: 'cache_'
     *   - sub: (integer) If non-zero, the number of subdirectories to create
     *          to store the file (i.e. PHP's session.save_path).
     *          DEFAULT: 0
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'prefix' => 'cache_',
            'sub' => 0
        ), $params);

        if (!isset($params['dir']) || !@is_dir($params['dir'])) {
            $params['dir'] = sys_get_temp_dir();
        }

        parent::__construct($params);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $c_time = time();

        /* Only do garbage collection 0.1% of the time we create an object. */
        if (!empty($this->_params['no_gc']) ||
            (intval(substr($c_time, -3)) !== 0)) {
            return;
        }

        $this->_gc();
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
        $tmp_file = Horde_Util::getTempFile('HordeCache', true, $this->_params['dir']);
        if (isset($this->_params['umask'])) {
            chmod($tmp_file, 0666 & ~$this->_params['umask']);
        }

        if (file_put_contents($tmp_file, $data) === false) {
            throw new Horde_Cache_Exception('Cannot write to cache directory ' . $this->_params['dir']);
        }

        @rename($tmp_file, $filename);

        if ($lifetime &&
            ($fp = @fopen(dirname($filename) . '/' . self::GC_FILE, 'a'))) {
            // This may result in duplicate entries in GC_FILE, but we
            // will take care of these whenever we do GC and this is quicker
            // than having to check every time we access the file.
            fwrite($fp, $filename . "\t" . (time() + $lifetime) . "\n");
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
        return @unlink($this->_keyToFile($key));
    }

    /**
     */
    public function clear()
    {
        foreach ($this->_getCacheFiles() as $val) {
            @unlink($val);
        }
        foreach ($this->_getGCFiles() as $val) {
            @unlink($val);
        }
    }

    /**
     * Return a list of cache files.
     *
     * @param string $start  The directory to start searching.
     *
     * @return array  Pathnames to cache files.
     */
    protected function _getCacheFiles($start = null)
    {
        $paths = array();

        try {
            $it = empty($this->_params['sub'])
                ? new DirectoryIterator($this->_params['dir'])
                : new RecursiveIteratorIterator(new RecursiveDirectoryIterator($start ?: $this->_params['dir']), RecursiveIteratorIterator::CHILD_FIRST);
        } catch (UnexpectedValueException $e) {
            return $paths;
        }

        foreach ($it as $val) {
            if (!$val->isDir() &&
                ($fname = $val->getFilename()) &&
                (strpos($fname, $this->_params['prefix']) === 0)) {
                $paths[$fname] = $val->getPathname();
            }
        }

        return $paths;
    }

    /**
     * Return a list of GC indexes.
     *
     * @return array  Pathnames to GC indexes.
     */
    protected function _getGCFiles()
    {
        $glob = $this->_params['dir'];
        if (!empty($this->_params['sub'])) {
            $glob .= '/'
                . implode('/', array_fill(0, $this->_params['sub'], '*'));
        }
        $glob .= '/' . self::GC_FILE;
        return glob($glob);
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
            $dir = $this->_params['dir'] . '/';
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

    /**
     * Garbage collector.
     */
    protected function _gc()
    {
        $c_time = time();

        if (!empty($this->_params['sub']) &&
            file_exists($this->_params['dir'] . '/' . self::GC_FILE)) {
            // If we cannot migrate, we cannot GC either, because we expect the
            // new format.
            try {
                $this->_migrateGc();
            } catch (Horde_Cache_Exception $e) {
                return;
            }
        }

        foreach ($this->_getGCFiles() as $filename) {
            $excepts = array();
            if (is_readable($filename)) {
                $fp = fopen($filename, 'r');
                while (!feof($fp) && ($data = fgets($fp))) {
                    $parts = explode("\t", trim($data), 2);
                    $excepts[$parts[0]] = $parts[1];
                }
            }

            foreach ($this->_getCacheFiles(dirname($filename)) as $pname) {
                if (!empty($excepts[$pname]) &&
                    ($c_time > $excepts[$pname])) {
                    @unlink($pname);
                    unset($excepts[$pname]);
                }
            }

            if ($fp = @fopen($filename, 'w')) {
                foreach ($excepts as $key => $val) {
                    fwrite($fp, $key . "\t" . $val . "\n");
                }
                fclose($fp);
            }
        }
    }

    /**
     * Migrates single GC indexes to per-directory indexes.
     */
    protected function _migrateGc()
    {
        // Read the old GC index.
        $filename = $this->_params['dir'] . '/' . self::GC_FILE;
        if (!is_readable($filename)) {
            return;
        }

        $fhs = array();
        $fp = fopen($filename, 'r');
        if (!flock($fp, LOCK_EX)) {
            throw new Horde_Cache_Exception('Cannot acquire lock for old garbage collection index');
        }

        // Loops through all cached files from the old index and write their GC
        // information to the new GC indexes.
        while (!feof($fp) && ($data = fgets($fp))) {
            list($path, $time) = explode("\t", trim($data), 2);
            $dir = dirname($path);
            if ($dir == $this->_params['dir']) {
                continue;
            }
            if (!isset($fhs[$dir])) {
                $fhs[$dir] = @fopen($dir . '/' . self::GC_FILE, 'a');
                // Maybe too many open file handles?
                if (!$fhs[$dir] && count($fhs)) {
                    unset($fhs[$dir]);
                    foreach ($fhs as $fh) {
                        fclose($fh);
                    }
                    $fhs = array();
                    $fhs[$dir] = @fopen($dir . '/' . self::GC_FILE, 'a');
                }
                if (!$fhs[$dir]) {
                    throw new Horde_Cache_Exception('Cannot migrate to new garbage collection index format');
                }
            }
            fwrite($fhs[$dir], $path . "\t" . $time . "\n");
        }

        // Clean up.
        foreach ($fhs as $fh) {
            fclose($fh);
        }
        fclose($fp);
        unlink($filename);
    }
}
