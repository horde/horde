<?php
/**
 * Preferences storage implementation using files in a directory
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_Storage_File extends Horde_Prefs_Storage_Base
{
    /* Current version number of the data format */
    const VERSION = 2;

    /**
     * Cached unserialized data of all scopes.
     *
     * @var array
     */
    protected $_fileCache = null;

    /**
     * Full path to the current preference file.
     *
     * @var string
     */
    protected $_fullpath;

    /**
     * Constructor.
     *
     * @param string $user   The username.
     * @param array $params  Configuration parameters:
     * <pre>
     * 'directory' - (string) [REQUIRED] Preference storage directory.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($user, array $params = array())
    {
        // Sanity check for directory
        if (empty($params['directory']) || !is_dir($params['directory'])) {
            throw new InvalidArgumentException('Preference storage directory is not available.');
        }
        if (!is_writable($params['directory'])) {
            throw new InvalidArgumentException(sprintf('Directory %s is not writeable.', $params['directory']));
        }

        parent::__construct($user, $params);

        $this->_fullpath = $this->_params['directory'] . '/' . basename($this->_params['user']) . '.prefs';
    }

    /**
     * Retrieves the requested preferences scope from the storage backend.
     *
     * @param Horde_Prefs_Scope $scope_ob  The scope object.
     *
     * @return Horde_Prefs_Scope  The modified scope object.
     * @throws Horde_Prefs_Exception
     */
    public function get($scope_ob)
    {
        if ($this->_loadFileCache() &&
            isset($this->_fileCache[$scope_ob->scope])) {
            foreach ($this->_fileCache[$scope_ob->scope] as $name => $val) {
                $scope_ob->set($name, $val);
            }
        }

        return $scope_ob;
    }

    /**
     * Load the preferences from the files.
     *
     * @return boolean  True on success.
     * @throws Horde_Prefs_Exception
     */
    protected function _loadFileCache()
    {
        if (is_null($this->_fileCache)) {
            // Try to read
            if (!file_exists($this->_fullpath)) {
                $this->_fileCache = array(
                    '__file_version' => self::VERSION
                );
                return false;
            }

            $this->_fileCache = @unserialize(file_get_contents($this->_fullpath));

            // Check version number. We can call format transformations hooks
            // in the future.
            if (!is_array($this->_fileCache) ||
                !array_key_exists('__file_version', $this->_fileCache) ||
                !($this->_fileCache['__file_version'] == self::VERSION)) {
                if ($this->_fileCache['__file_version'] == 1) {
                    $this->updateFileFormat();
                } else {
                    throw new Horde_Prefs_Exception(sprintf('Wrong version number found: %s (should be %d)', $this->_fileCache['__file_version'], self::VERSION));
                }
            }
        }

        return true;
    }

    /**
     * Stores changed preferences in the storage backend.
     *
     * @param Horde_Prefs_Scope $scope_ob  The scope object.
     *
     * @throws Horde_Prefs_Exception
     */
    public function store($scope_ob)
    {
        $this->_loadFileCache();

        /* Driver has no support for storing locked status. */
        foreach ($scope_ob->getDirty() as $name) {
            $value = $scope_ob->get($name);
            if (is_null($value)) {
                unset($this->_fileCache[$scope_ob->scope][$name]);
            } else {
                $this->_fileCache[$scope_ob->scope][$name] = $value;
            }
        }

        $tmp_file = Horde_Util::getTempFile('PrefsFile', true, $this->_params['directory']);

        if ((file_put_contents($tmp_file, serialize($this->_fileCache)) === false) ||
            (@rename($tmp_file, $this->_fullpath) === false)) {
            throw new Horde_Prefs_Exception(sprintf('Write of preferences to %s failed', $this->_fullpath));
        }
    }

    /**
     * Removes preferences from the backend.
     *
     * @param string $scope  The scope of the prefs to clear. If null, clears
     *                       all scopes.
     * @param string $pref   The pref to clear. If null, clears the entire
     *                       scope.
     *
     * @throws Horde_Db_Exception
     */
    public function remove($scope = null, $pref = null)
    {
        // TODO
    }

    /* Helper functions. */

    /**
     * Updates format of file.
     */
    public function updateFileFormat()
    {
        $new_vers = array('__file_version' => self::VERSION);
        unset($this->_fileCache['__file_version']);

        foreach ($this->_fileCache as $scope => $prefs) {
            foreach ($prefs as $name => $pref) {
                $new_vers[$scope][$name] = $pref['v'];
            }
        }

        $this->_fileCache = $new_vers;
    }

    /**
     * Lists all available scopes.
     *
     * @since Horde_Prefs 1.1.0
     *
     * @return array The list of scopes stored in the backend.
     */
    public function listScopes()
    {
        $this->_loadFileCache();
        return array_diff(
            array_keys($this->_fileCache), array('__file_version')
        );
    }
}
