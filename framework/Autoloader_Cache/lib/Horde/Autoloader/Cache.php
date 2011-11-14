<?php
/**
 * Decorator for Horde_Autoloader that implements caching of class-file-maps.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Autoloader_Cache
 */
require_once 'Horde/Autoloader/Default.php';

class Horde_Autoloader_Cache extends Horde_Autoloader_Default
{
    /**
     * Map of all classes already looked up.
     *
     * @var array
     */
    protected $_cache;

    /**
     * Constructor.
     *
     * Tries all supported cache backends and tries to retrieved the
     * cached class map.
     */
    public function __construct()
    {
        parent::__construct();
        if (extension_loaded('apc')) {
            $this->_cache = apc_fetch('horde_autoloader_cache');
        } elseif (extension_loaded('xcache')) {
            $this->_cache = xcache_get('horde_autoloader_cache');
        } elseif (extension_loaded('eaccelerator')) {
            $this->_cache = eaccelerator_get('horde_autoloader_cache');
        } elseif (($tempdir = sys_get_temp_dir()) &&
                  is_readable($tempdir . '/horde_autoloader_cache')) {
            $this->_cache = @json_decode(file_get_contents($tempdir . '/horde_autoloader_cache'), true);
        }
    }

    /**
     * Destructor.
     *
     * Tries all supported cache backends and tries to save the class
     * map to the cache.
     */
    public function __destruct()
    {
        if (extension_loaded('apc')) {
            apc_store('horde_autoloader_cache', $this->_cache);
        } elseif (extension_loaded('xcache')) {
            xcache_set('horde_autoloader_cache', $this->_cache);
        } elseif (extension_loaded('eaccelerator')) {
            eaccelerator_put('horde_autoloader_cache', $this->_cache);
        } elseif (($tempdir = sys_get_temp_dir()) &&
                  (is_writable($tempdir . '/horde_autoloader_cache') ||
                   (!file_exists($tempdir . '/horde_autoloader_cache') &&
                    is_writable($tempdir)))) {
            file_put_contents($tempdir . '/horde_autoloader_cache', json_encode($this->_cache));
        }
    }

    /**
     * Search registered mappers in LIFO order.
     */
    public function mapToPath($className)
    {
        if (!isset($this->_cache[$className])) {
            $this->_cache[$className] = parent::mapToPath($className);
        }
        return $this->_cache[$className];
    }
}

spl_autoload_unregister(array($__autoloader, 'loadClass'));
$__autoloader = new Horde_Autoloader_Cache();
$__autoloader->registerAutoloader();
