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
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_Storage_File extends Horde_Prefs_Storage
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
     * @param array $params  Configuration parameters:
     * <pre>
     * 'directory' - (string) [REQUIRED] Preference storage directory.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        // Sanity check for directory
        if (empty($params['directory']) || !is_dir($params['directory'])) {
            throw new InvalidArgumentException('Preference storage directory is not available.');
        }
        if (!is_writable($params['directory'])) {
            throw new InvalidArgumentException(sprintf('Directory %s is not writeable.', $params['directory']));
        }

        parent::__construct($scope, $opts, $params);

        $this->_fullpath = $this->params['directory'] . '/' . basename($this->_params['user']) . '.prefs';
    }

    /**
     */
    public function get($scope)
    {
        if (!isset($this->_params['directory'])) {
            return false;
        }

        if (is_null($this->_fileCache)) {
            // Try to read
            if (!file_exists($this->_fullpath)) {
                return false;
            }
            $this->_fileCache = unserialize(file_get_contents($this->_fullpath));

            // Check version number. We can call format transformations hooks
            // in the future.
            if (!is_array($this->_fileCache) ||
                !array_key_exists('__file_version', $this->_fileCache) ||
                !($this->_fileCache['__file_version'] == self::VERSION)) {
                if ($this->_fileCache['__file_version'] == 1) {
                    $this->transformV1V2();
                } else {
                    throw new Horde_Prefs_Exception(sprintf('Wrong version number found: %s (should be %d)', $this->_fileCache['__file_version'], self::VERSION));
                }
            }
        }

        // Check if the scope exists
        if (empty($scope) || !isset($this->_fileCache[$scope])) {
            return false;
        }

        $ret = array();

        foreach ($this->_fileCache[$scope] as $name => $val) {
            $ret[$name] = $val;
        }

        return $ret;
    }

    /**
     */
    public function store($prefs)
    {
        if (!isset($this->_params['directory'])) {
            return;
        }

        // Read in all existing preferences, if any.
        $this->get('');
        if (!is_array($this->_fileCache)) {
            $this->_fileCache = array('__file_version' => self::VERSION);
        }

        foreach ($prefs as $scope => $p) {
            foreach ($p as $name => $val) {
                $this->_fileCache[$scope][$name] = $pref['v'];
            }
        }

        $tmp_file = Horde_Util::getTempFile('PrefsFile', true, $this->_params['directory']);

        if ((file_put_contents($tmp_file, serialize($this->_fileCache)) === false) ||
            (@rename($tmp_file, $this->_fullpath) === false)) {
            throw new Horde_Prefs_Exception(sprintf('Write of preferences to %s failed', $this->_filename));
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        // TODO
    }

    /* Helper functions. */

    /**
     * Transforms the broken version 1 format into version 2.
     */
    public function transformV1V2()
    {
        $version2 = array('__file_version' => 2);
        foreach ($this->_fileCache as $scope => $prefs) {
            if ($scope != '__file_version') {
                foreach ($prefs as $name => $pref) {
                    $version2[$scope][$name] = $pref['v'];
                }
            }
        }

        $this->_fileCache = $version2;
    }

}
