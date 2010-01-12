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
 * @package  Horde_Prefs
 */
class Horde_Prefs_File extends Horde_Prefs
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
     * @param string $scope     The current preferences scope.
     * @param string $user      The user who owns these preferences.
     * @param string $password  The password associated with $user. (Unused)
     * @param array $params     A hash containing connection parameters.
     * @param boolean $caching  Should caching be used?
     */
    public function __construct($scope, $user, $password, $params, $caching)
    {
        parent::__construct($scope, $user, $password, $params, $caching);

        // Sanity check for directory
        $error = false;
        if (empty($params['directory']) || !is_dir($params['directory'])) {
            Horde::logMessage(_("Preference storage directory is not available."), __FILE__, __LINE__, PEAR_LOG_ERR);
            $error = true;
        } elseif (!is_writable($params['directory'])) {
            Horde::logMessage(sprintf(_("Directory %s is not writeable"), $params['directory']), __FILE__, __LINE__, PEAR_LOG_ERR);
            $error = true;
        }

        if ($error) {
            $this->_dirname = null;
            $this->_fullpath = null;

            if (isset($GLOBALS['notification'])) {
                $GLOBALS['notification']->push(_("The preferences backend is currently unavailable and your preferences have not been loaded. You may continue to use the system with default settings."));
            }
        } else {
            $this->_dirname = $params['directory'];
            $this->_fullpath = $this->_dirname . '/' . basename($user) . '.prefs';
        }
    }

    /**
     * Retrieves the requested set of preferences from the current session.
     *
     * @param string $scope  Scope specifier.
     *
     * @throws Horde_Exception
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
                    throw new Horde_Exception(sprintf('Wrong version number found: %s (should be %d)', $this->_fileCache['__file_version'], $this->_version));
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
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    public function store()
    {
        if (is_null($this->_dirname)) {
            return false;
        }

        // Get the list of preferences that have changed. If there are
        // none, no need to hit the backend.
        $dirty_prefs = $this->_dirtyPrefs();
        if (!$dirty_prefs) {
            return true;
        }

        // Read in all existing preferences, if any.
        $this->_retrieve('');
        if (!is_array($this->_fileCache)) {
            $this->_fileCache = array('__file_version' => $this->_version);
        }

        // Update all values from dirty scope
        foreach ($dirty_prefs as $scope => $prefs) {
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
            throw new Horde_Exception('Write of preferences to %s failed', $this->_filename);
        }

        return true;
    }

}
