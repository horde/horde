<?php
/**
 * Preferences storage implementation using files in a directory
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_File extends Horde_Prefs_Base
{
    /**
     * Current version number of the data format
     *
     * @var int
     */
    protected $_version = 2;

    /**
     * Directory to store the preferences
     *
     * @var string
     */
    protected $_dirname;

    /**
     * Full path to the current preference file
     *
     * @var string
     */
    protected $_fullpath;

    /**
     * Cached unserialized data of all scopes
     *
     * @var array
     */
    protected $_fileCache = null;

    /**
     * Constructor.
     *
     * @param string $scope  The scope for this set of preferences.
     * @param array $opts    See factory() for list of options.
     * @param array $params  Additional parameters:
     * <pre>
     * 'directory' - (string) [REQUIRED] Preference storage directory.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($scope, $opts, $params);
    {
        parent::__construct($scope, $opts, $params);

        // Sanity check for directory
        if (empty($params['directory']) || !is_dir($params['directory'])) {
            throw new InvalidArgumentException('Preference storage directory is not available.');
        }
        if (!is_writable($params['directory'])) {
            throw new InvalidArgumentException(sprintf('Directory %s is not writeable.', $params['directory']));
        }

        $this->_dirname = $params['directory'];
        $this->_fullpath = $this->_dirname . '/' . basename($user) . '.prefs';
    }

    /**
     * Retrieves the requested set of preferences from the current session.
     *
     * @param string $scope  Scope specifier.
     *
     * @throws Horde_Prefs_Exception
     */
    protected function _retrieve($scope)
    {
        if (is_null($this->_dirname)) {
            return;
        }

        if (is_null($this->_fileCache)) {
            // Try to read
            $this->_fileCache = $this->_readCache();
            if (is_null($this->_fileCache)) {
                return;
            }

            // Check version number. We can call format transformations hooks
            // in the future.
            if (!is_array($this->_fileCache) ||
                !array_key_exists('__file_version', $this->_fileCache) ||
                !($this->_fileCache['__file_version'] == $this->_version)) {
                if ($this->_fileCache['__file_version'] == 1) {
                    $this->transformV1V2();
                } else {
                    throw new Horde_Prefs_Exception(sprintf('Wrong version number found: %s (should be %d)', $this->_fileCache['__file_version'], $this->_version));
                }
            }
        }

        // Check if the scope exists
        if (empty($scope) || !array_key_exists($scope, $this->_fileCache)) {
            return;
        }

        // Merge config values
        foreach ($this->_fileCache[$scope] as $name => $val) {
            if (isset($this->_scopes[$scope][$name])) {
                $this->_scopes[$scope][$name]['v'] = $val;
                $this->_scopes[$scope][$name]['m'] &= ~self::PREFS_DEFAULT;
            } else {
                // This is a shared preference.
                $this->_scopes[$scope][$name] = array('v' => $val,
                                                      'm' => 0,
                                                      'd' => null);
            }
        }
    }

    /**
     * Read data from disk.
     *
     * @return mixed  Data array on success or null on error.
     */
    protected function _readCache()
    {
        return file_exists($this->_fullpath)
            ? unserialize(file_get_contents($this->_fullpath))
            : null;
    }

    /**
     * Transforms the broken version 1 format into version 2.
     */
    public function transformV1V2()
    {
        $version2 = array('__file_version' => 2);
        foreach ($this->_fileCache as $scope => $prefs) {
            if ($scope != '__file_version') {
                foreach ($prefs as $name => $pref) {
                    /* Default values should not have been stored by the
                     * driver. They are being set via the prefs.php files. */
                    if (!($pref['m'] & self::PREFS_DEFAULT)) {
                        $version2[$scope][$name] = $pref['v'];
                    }
                }
            }
        }
        $this->_fileCache = $version2;
    }

    /**
     * Write data to disk
     *
     * @return boolean  True on success.
     */
    protected function _writeCache()
    {
        $tmp_file = Horde_Util::getTempFile('PrefsFile', true, $this->_dirname);

        $data = serialize($this->_fileCache);

        if (file_put_contents($tmp_file, $data) === false) {
            return false;
        }

        return @rename($tmp_file, $this->_fullpath);
    }

    /**
     * Stores preferences in the current session.
     *
     * @throws Horde_Prefs_Exception
     */
    public function store()
    {
        if (is_null($this->_dirname) || empty($this->_dirty)) {
            return;
        }

        // Read in all existing preferences, if any.
        $this->_retrieve('');
        if (!is_array($this->_fileCache)) {
            $this->_fileCache = array('__file_version' => $this->_version);
        }

        // Update all values from dirty scope
        foreach ($this->_dirty as $scope => $prefs) {
            foreach ($prefs as $name => $pref) {
                // Don't store locked preferences.
                if (!($this->_scopes[$scope][$name]['m'] & self::LOCKED)) {
                    $this->_fileCache[$scope][$name] = $pref['v'];

                    // Clean the pref since it was just saved.
                    $this->_scopes[$scope][$name]['m'] &= ~self::DIRTY;
                }
            }
        }

        if ($this->_writeCache() == false) {
            throw new Horde_Prefs_Exception(sprintf('Write of preferences to %s failed', $this->_filename));
        }
    }

}
