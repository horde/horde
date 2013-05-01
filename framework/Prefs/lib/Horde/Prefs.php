<?php
/**
 * The Horde_Prefs:: class provides a common abstracted interface into the
 * various preferences storage mediums.  It also includes all of the
 * functions for retrieving, storing, and checking preference values.
 *
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */
class Horde_Prefs implements ArrayAccess
{
    /* The default scope name. */
    const DEFAULT_SCOPE = 'horde';

    /**
     * Caching object.
     *
     * @var Horde_Prefs_Cache
     */
    protected $_cache;

    /**
     * General library options.
     *
     * @var array
     */
    protected $_opts = array(
        'cache' => null,
        'logger' => null,
        'password' => '',
        'sizecallback' => null,
        'storage' => null,
        'user' => ''
    );

    /**
     * String containing the name of the current scope. This is used
     * to differentiate between sets of preferences. By default, preferences
     * belong to this scope.
     *
     * @var string
     */
    protected $_scope = self::DEFAULT_SCOPE;

    /**
     * Scope list.  Keys are scope names, values are Horde_Prefs_Scope
     * objects.
     *
     * @var array
     */
    protected $_scopes = array();

    /**
     * The storage driver(s).
     *
     * @var array
     */
    protected $_storage;

    /**
     * Constructor.
     *
     * @param string $scope   The scope for this set of preferences.
     * @param mixed $storage  The storage object(s) to use. Either a single
     *                        Horde_Prefs_Storage object, or an array of
     *                        objects.
     * @param array $opts     Additional confguration options:
     * <pre>
     * cache - (Horde_Prefs_Cache) The cache driver to use.
     *         DEFAULT: No caching.
     * logger - (Horde_Log_Logger) Logging object.
     *          DEFAULT: NONE
     * password - (string) The password associated with 'user'.
     *            DEFAULT: NONE
     * sizecallback - (callback) If set, called when setting a value in the
     *                backend.
     *                DEFAULT: NONE
     * user - (string) The name of the user who owns this set of preferences.
     *        DEFAULT: NONE
     * </pre>
     */
    public function __construct($scope, $storage = null, array $opts = array())
    {
        $this->_opts = array_merge($this->_opts, $opts);

        $this->_cache = isset($this->_opts['cache'])
            ? $this->_opts['cache']
            : new Horde_Prefs_Cache_Null($this->getUser());

        $this->_scope = $scope;

        if (is_null($storage)) {
            $storage = array(new Horde_Prefs_Storage_Null($this->getUser()));
        } elseif (!is_array($storage)) {
            $storage = array($storage);
        }
        $this->_storage = $storage;

        register_shutdown_function(array($this, 'store'), false);

        $this->retrieve($scope);
    }

    /**
     * Return the user who owns these preferences.
     *
     * @return string  The user these preferences are for.
     */
    public function getUser()
    {
        return $this->_opts['user'];
    }

    /**
     * Get the current scope.
     *
     * @return string  The current scope (application).
     */
    public function getScope()
    {
        return $this->_scope;
    }

    /**
     * Returns the storage drivers.
     *
     * @return array  The storage drivers.
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    /**
     * Removes a preference entry from the $prefs hash.
     *
     * @param string $pref  The name of the preference to remove.  If null,
     *                      removes all prefs.
     */
    public function remove($pref = null)
    {
        $to_remove = array();

        if (is_null($pref)) {
            foreach ($this->_scopes as $key => $val) {
                $to_remove[$key] = array_keys(iterator_to_array($val));
            }
        } elseif ($scope = $this->_getScope($pref)) {
            $to_remove[$scope] = array($pref);
        }

        foreach ($to_remove as $key => $val) {
            $scope = $this->_scopes[$key];

            foreach ($val as $prefname) {
                $scope->remove($prefname);

                foreach ($this->_storage as $storage) {
                    try {
                        $storage->remove($scope->scope, $prefname);
                    } catch (Exception $e) {}
                }
            }
        }
    }

    /**
     * Sets the given preference to the specified value if the preference is
     * modifiable.
     *
     * @param string $pref  The preference name to modify.
     * @param string $val   The preference value (UTF-8).
     * @param array $opts   Additional options:
     * <pre>
     * nosave - (boolean) If true, the preference will not be saved to the
     *          storage backend(s).
     * </pre>
     *
     * @return boolean  True if the value was successfully set, false on a
     *                  failure.
     * @throws Horde_Prefs_Exception
     */
    public function setValue($pref, $val, array $opts = array())
    {
        /* Exit early if preference doesn't exist or is locked. */
        if (!($scope = $this->_getScope($pref)) ||
            $this->_scopes[$scope]->isLocked($pref)) {
            return false;
        }

        // Check to see if the value exceeds the allowable storage limit.
        if ($this->_opts['sizecallback'] &&
            call_user_func($this->_opts['sizecallback'], $pref, strlen($val))) {
            return false;
        }

        $this->_scopes[$scope]->set($pref, $val);
        if (!empty($opts['nosave'])) {
            $this->_scopes[$scope]->setDirty($pref, false);
        }

        foreach ($this->_storage as $storage) {
            $storage->onChange($scope, $pref);
        }

        if ($this->_opts['logger']) {
            $this->_opts['logger']->log(__CLASS__ . ': Storing preference value (' . $pref . ')', 'DEBUG');
        }

        return true;
    }

    /**
     * Shortcut to setValue().
     */
    public function __set($name, $value)
    {
        return $this->setValue($name, $value);
    }

    /**
     * Returns the value of the requested preference.
     *
     * @param string $pref  The preference name.
     *
     * @return string  The value of the preference (UTF-8), null if it doesn't
     *                 exist.
     */
    public function getValue($pref)
    {
        return ($scope = $this->_getScope($pref))
            ? $this->_scopes[$scope]->get($pref)
            : null;
    }

    /**
     * Shortcut to getValue().
     */
    public function __get($name)
    {
        return $this->getValue($name);
    }

    /**
     * Mark a preference as locked.
     *
     * @param string $pref     The preference name.
     * @param boolean $locked  Is the preference locked?
     */
    public function setLocked($pref, $bool)
    {
        if ($scope = $this->_getScope($pref)) {
            $this->_scopes[$scope]->setLocked($pref, $bool);
        }
    }

    /**
     * Is a preference locked?
     *
     * @param string $pref  The preference name.
     *
     * @return boolean  Whether the preference is locked.
     */
    public function isLocked($pref)
    {
        return ($scope = $this->_getScope($pref))
            ? $this->_scopes[$scope]->isLocked($pref)
            : false;
    }

    /**
     * Is a preference marked dirty?
     *
     * @param string $pref  The preference name.
     *
     * @return boolean  True if the preference is marked dirty.
     */
    public function isDirty($pref)
    {
        return ($scope = $this->_getScope($pref))
            ? $this->_scopes[$scope]->isDirty($pref)
            : false;
    }

    /**
     * Returns the default value of the given preference.
     *
     * @param string $pref  The name of the preference to get the default for.
     *
     * @return string  The preference's default value.
     */
    public function getDefault($pref)
    {
        return ($scope = $this->_getScope($pref))
            ? $this->_scopes[$scope]->getDefault($pref)
            : null;
    }

    /**
     * Determines if the current preference value is the default value.
     *
     * @param string $pref  The name of the preference to check.
     *
     * @return boolean  True if the preference is the application default
     *                  value.
     */
    public function isDefault($pref)
    {
        return ($scope = $this->_getScope($pref))
            ? $this->_scopes[$scope]->isDefault($pref)
            : false;
    }

    /**
     * Returns the scope of a preference.
     *
     * @param string $pref  The preference name.
     *
     * @return mixed  The scope of the preference, or null if it doesn't
     *                exist.
     */
    protected function _getScope($pref)
    {
        if ($this->_scopes[$this->_scope]->exists($pref)) {
            return $this->_scope;
        } elseif (($this->_scope != self::DEFAULT_SCOPE) &&
            ($this->_scopes[self::DEFAULT_SCOPE]->exists($pref))) {
            return self::DEFAULT_SCOPE;
        }

        return null;
    }

    /**
     * Retrieves preferences for the current scope.
     *
     * @param string $scope  Optional scope specifier - if not present the
     *                       current scope will be used.
     */
    public function retrieve($scope = null)
    {
        if (is_null($scope)) {
            $scope = $this->getScope();
        } else {
            $this->_scope = $scope;
        }

        $this->_loadScope(self::DEFAULT_SCOPE);
        if ($scope != self::DEFAULT_SCOPE) {
            $this->_loadScope($scope);
        }
    }

    /**
     * Load a specific preference scope.
     *
     * @param string $scope  The scope to load.
     */
    protected function _loadScope($scope)
    {
        // Return if we've already loaded these prefs.
        if (!empty($this->_scopes[$scope])) {
            return;
        }

        // Now check the prefs cache for existing values.
        try {
            if ((($cached = $this->_cache->get($scope)) !== false) &&
                ($cached instanceof Horde_Prefs_Scope)) {
                $this->_scopes[$scope] = $cached;
                return;
            }
        } catch (Horde_Prefs_Exception $e) {}

        $scope_ob = new Horde_Prefs_Scope($scope);
        $scope_ob->init = true;

        // Need to set object in scopes array now, since the storage object
        // might recursively call the prefs object.
        $this->_scopes[$scope] = $scope_ob;

        foreach ($this->_storage as $storage) {
            $scope_ob = $storage->get($scope_ob);
        }

        $scope_ob->init = false;

        $this->_cache->store($scope_ob);
    }

    /**
     * Save all dirty prefs to the storage backend.
     *
     * @param boolean $throw  Throw exception on error? If false, ignores
     *                        errors. (Since 2.1.0)
     */
    public function store($throw = true)
    {
        foreach ($this->_scopes as $scope) {
            if ($scope->isDirty()) {
                foreach ($this->_storage as $storage) {
                    try {
                        $storage->store($scope);
                    } catch (Exception $e) {
                        if ($throw) {
                            throw $e;
                        }
                    }
                }

                try {
                    $this->_cache->store($scope);
                } catch (Exception $e) {
                    if ($throw) {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Cleanup (e.g. remove) scope(s).
     *
     * @param boolean $all  Cleanup all scopes. If false, clean present scope
     *                      only.
     */
    public function cleanup($all = false)
    {
        if ($all) {
            /* Destroy all scopes. */
            $this->_scopes = array();
            $scope = null;
        } else {
            unset($this->_scopes[$this->_scope]);
            $scope = $this->_scope;
        }

        try {
            $this->_cache->remove($scope);
        } catch (Horde_Prefs_Exception $e) {}
    }

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        return !is_null($this->getValue($offset));
    }

    public function offsetGet($offset)
    {
        return $this->getValue($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setValue($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

}
