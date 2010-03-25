<?php
/**
 * The Horde_Prefs:: class provides a common abstracted interface into the
 * various preferences storage mediums.  It also includes all of the
 * functions for retrieving, storing, and checking preference values.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs
{
    /** Preference is administratively locked. */
    const LOCKED = 1;

    /** Preference is shared amongst applications. */
    const SHARED = 2;

    /** Preference value has been changed. */
    const DIRTY = 4;

    /** Preference value is the application default.
     *  DEFAULT is a reserved PHP constant. */
    const PREFS_DEFAULT = 8;

    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * Hash holding the current set of preferences. Each preference is
     * itself a hash, so this will ultimately be multi-dimensional.
     *
     * [*pref name*] => Array(
     *     [d] => (string) Default value
     *     [m] => (integer) Pref mask
     *     [v] => (string) Current pref value
     * )
     *
     * @var array
     */
    protected $_prefs = array();

    /**
     * String containing the name of the current scope. This is used
     * to differentiate between sets of preferences (multiple
     * applications can have a "sortby" preference, for example). By
     * default, all preferences belong to the "global" (Horde) scope.
     *
     * @var string
     */
    protected $_scope = 'horde';

    /**
     * Array of loaded scopes. In order to only load what we need, and
     * to not load things multiple times, we need to maintain a list
     * of loaded scopes. $this->_prefs will always be the combination
     * of the current scope and the 'horde' scope (or just the 'horde'
     * scope).
     *
     * @var array
     */
    protected $_scopes = array();

    /**
     * String containing the current username. This indicates the owner of the
     * preferences.
     *
     * @var string
     */
    protected $_user = '';

    /**
     * Boolean indicating whether preference caching should be used.
     *
     * @var boolean
     */
    protected $_caching = false;

    /**
     * Array to cache in. Usually a reference to an array in $_SESSION, but
     * could be overridden by a subclass for testing or other users.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Hash holding preferences with hook functions defined.
     *
     * @var array
     */
    protected $_hooks = array();

    /**
     * Attempts to return a reference to a concrete instance based on $driver.
     * It will only create a new instance if no instance with the same
     * parameters currently exists.
     *
     * This should be used if multiple preference sources (and, thus,
     * multiple instances) are required.
     *
     * @param mixed $driver     The type of concrete subclass to return.
     * @param string $scope     The scope for this set of preferences.
     * @param string $user      The name of the user who owns this set of
     *                          preferences.
     * @param string $password  The password associated with $user.
     * @param array $params     A hash containing any additional configuration
     *                          or connection parameters a subclass might need.
     * @param boolean $caching  Should caching be used?
     *
     * @return Horde_Prefs  The concrete reference, or false on an error.
     * @throws Horde_Exception
     */
    static public function singleton($driver, $scope = 'horde', $user = '',
                                     $password = '', $params = null,
                                     $caching = true)
    {
        if (is_null($params)) {
            $params = Horde::getDriverConfig('prefs', $driver);
        }

        $signature = serialize(array($driver, $user, $params, $caching));
        if (!isset(self::$_instances[$signature])) {
            self::$_instances[$signature] = self::factory($driver, $scope, $user, $password, $params, $caching);
        }

        /* Preferences may be cached with a different scope. */
        self::$_instances[$signature]->setScope($scope);

        return self::$_instances[$signature];
    }

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param mixed $driver     The type of concrete subclass to return.
     * @param string $scope     The scope for this set of preferences.
     * @param string $user      The name of the user who owns this set of
     *                          preferences.
     * @param string $password  The password associated with $user.
     * @param array $params     A hash containing any additional configuration
     *                          or connection parameters a subclass might need.
     * @param boolean $caching  Should caching be used?
     *
     * @return Horde_Prefs  The newly created concrete instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $scope = 'horde', $user = '',
                                   $password = '', $params = null,
                                   $caching = true)
    {
        $driver = ucfirst(basename($driver));
        if (empty($driver) || $driver == 'None') {
            $driver = 'Session';
        }

        $class = __CLASS__ . '_' . $driver;
        if (!class_exists($class)) {
            throw new Horde_Exception('Class definition of ' . $class . ' not found.');
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('prefs', $driver);
        }

        /* If $params['user_hook'] is defined, use it to retrieve the value to
         * use for the username ($this->_user). Otherwise, just use the value
         * passed in the $user parameter. */
        if (!empty($params['user_hook']) &&
            function_exists($params['user_hook'])) {
            $user = call_user_func($params['user_hook'], $user);
        }

        $prefs = new $class($scope, $user, $password, $params, $caching);
        $prefs->retrieve($scope);

        return $prefs;
    }

    /**
     * Constructor.
     *
     * @param string $scope     The scope for this set of preferences.
     * @param string $user      The name of the user who owns this set of
     *                          preferences.
     * @param string $password  The password associated with $user.
     * @param array $params     A hash containing any additional configuration
     *                          or connection parameters a subclass might need.
     * @param boolean $caching  Should caching be used?
     *
     */
    protected function __construct($scope, $user, $password, $params, $caching)
    {
        register_shutdown_function(array($this, 'store'));

        $this->_user = $user;
        $this->_password = $password;
        $this->_scope = $scope;
        $this->_params = $params;
        $this->_caching = $caching;

        // Create a unique key that's safe to use for caching even if we want
        // another user's preferences later, then register the cache array in
        // $_SESSION.
        if ($this->_caching) {
            $cacheKey = 'horde_prefs_' . hash('sha1', $this->_user);

            // Store a reference to the $_SESSION array.
            $this->_cache = &$_SESSION[$cacheKey];
        }
    }

    /**
     * Returns the charset used by the concrete preference backend.
     *
     * @return string  The preference backend's charset.
     */
    public function getCharset()
    {
        return Horde_Nls::getCharset();
    }

    /**
     * Return the user who owns these preferences.
     *
     * @return string  The user these preferences are for.
     */
    public function getUser()
    {
        return $this->_user;
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
     * Change scope without explicitly retrieving preferences.
     *
     * @param string $scope  The new scope.
     */
    public function setScope($scope)
    {
        $this->_scope = $scope;
    }

    /**
     * Removes a preference entry from the $prefs hash.
     *
     * @param string $pref  The name of the preference to remove.
     */
    public function remove($pref)
    {
        // FIXME not updated yet.
        $scope = $this->_getPreferenceScope($pref);
        unset($this->_prefs[$pref]);
        unset($this->_cache[$scope][$pref]);
    }

    /**
     * Sets the given preferences ($pref) to the specified value
     * ($val), if the preference is modifiable.
     *
     * @param string $pref      The name of the preference to modify.
     * @param string $val       The new value for this preference.
     * @param boolean $convert  If true the preference value gets converted
     *                          from the current charset to the backend's
     *                          charset.
     *
     * @return boolean  True if the value was successfully set, false on a
     *                  failure.
     * @throws Horde_Exception
     */
    public function setValue($pref, $val, $convert = true)
    {
        /* Exit early if this preference is locked or doesn't exist. */
        if (!isset($this->_prefs[$pref]) || $this->isLocked($pref)) {
            return false;
        }

        $result = $this->_setValue($pref, $val, true, $convert);

        Horde::logMessage(__CLASS__ . ': Storing preference value (' . $pref . ')', 'DEBUG');

        if ($result && $this->isDirty($pref)) {
            $scope = $this->_getPreferenceScope($pref);
            $this->_cacheUpdate($scope, array($pref));

            /* If this preference has a change hook, call it now. */
            try {
                Horde::callHook('prefs_change_hook_' . $pref, array(), $scope);
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        return $result;
    }

    /**
     * Shortcut to setValue().
     */
    public function __set($name, $value)
    {
        return $this->setValue($name, $value);
    }

    /**
     * Sets the given preferences ($pref) to the specified value
     * ($val), whether or not the preference is user-modifiable, unset
     * the default bit, and set the dirty bit.
     *
     * @param string $pref      The name of the preference to modify.
     * @param string $val       The new value for this preference.
     * @param boolean $dirty    True if we should mark the new value as
     *                          dirty (changed).
     * @param boolean $convert  If true the preference value gets converted
     *                          from the current charset to the backend's
     *                          charset.
     *
     * @return boolean  True if the value was successfully set, false on a
     *                  failure.
     */
    protected function _setValue($pref, $val, $dirty = true, $convert = true)
    {
        global $conf;

        if ($convert) {
            $val = $this->convertToDriver($val, Horde_Nls::getCharset());
        }

        // If the preference's value is already equal to $val, don't
        // bother changing it. Changing it would set the "dirty" bit,
        // causing an unnecessary update later.
        if (isset($this->_prefs[$pref]) &&
            (($this->_prefs[$pref]['v'] == $val) &&
             !$this->isDefault($pref))) {
            return true;
        }

        // Check to see if the value exceeds the allowable storage
        // limit.
        if (isset($GLOBALS['conf']['prefs']['maxsize']) &&
            (strlen($val) > $GLOBALS['conf']['prefs']['maxsize']) &&
            isset($GLOBALS['notification'])) {
            $GLOBALS['notification']->push(sprintf(_("The preference \"%s\" could not be saved because its data exceeds the maximum allowable size"), $pref), 'horde.error');
            return false;
        }

        // Assign the new value, unset the "default" bit, and set the
        // "dirty" bit.
        if (empty($this->_prefs[$pref]['m'])) {
            $this->_prefs[$pref]['m'] = 0;
        }
        $this->_prefs[$pref]['v'] = $val;
        $this->setDefault($pref, false);
        if ($dirty) {
            $this->setDirty($pref, true);
        }

        // Finally, copy into the $_scopes array.
        $this->_scopes[$this->_getPreferenceScope($pref)][$pref] = $this->_prefs[$pref];

        return true;
    }

    /**
     * Returns the value of the requested preference.
     *
     * @param string $pref      The name of the preference to retrieve.
     * @param boolean $convert  If true the preference value gets converted
     *                          from the backend's charset to the current
     *                          charset.
     *
     * @return string  The value of the preference, null if it doesn't exist.
     */
    public function getValue($pref, $convert = true)
    {
        $value = null;

        if (isset($this->_prefs[$pref]['v'])) {
            if ($convert) {
                /* Default values have the current UI charset.
                 * Stored values have the backend charset. */
                $value = $this->isDefault($pref)
                    ? Horde_String::convertCharset($this->_prefs[$pref]['v'], Horde_Nls::getCharset(), Horde_Nls::getCharset())
                    : $this->convertFromDriver($this->_prefs[$pref]['v'], Horde_Nls::getCharset());
            } else {
                $value = $this->_prefs[$pref]['v'];
            }
        }

        return $value;
    }

    /**
     * Shortcut to getValue().
     */
    public function __get($name)
    {
        return $this->getValue($name);
    }

    /**
     * Modifies the "locked" bit for the given preference.
     *
     * @param string $pref   The name of the preference to modify.
     * @param boolean $bool  The new boolean value for the "locked" bit.
     */
    public function setLocked($pref, $bool)
    {
        $this->_setMask($pref, $bool, self::LOCKED);
    }

    /**
     * Returns the state of the "locked" bit for the given preference.
     *
     * @param string $pref  The name of the preference to check.
     *
     * @return boolean  The boolean state of $pref's "locked" bit.
     */
    public function isLocked($pref)
    {
        return $this->_getMask($pref, self::LOCKED);
    }

    /**
     * Modifies the "shared" bit for the given preference.
     *
     * @param string $pref   The name of the preference to modify.
     * @param boolean $bool  The new boolean value for the "shared" bit.
     */
    public function setShared($pref, $bool)
    {
        $this->_setMask($pref, $bool, self::SHARED);
    }

    /**
     * Returns the state of the "shared" bit for the given preference.
     *
     * @param string $pref  The name of the preference to check.
     *
     * @return boolean  The boolean state of $pref's "shared" bit.
     */
    public function isShared($pref)
    {
        return $this->_getMask($pref, self::SHARED);
    }

    /**
     * Modifies the "dirty" bit for the given preference.
     *
     * @param string $pref      The name of the preference to modify.
     * @param boolean $bool     The new boolean value for the "dirty" bit.
     */
    public function setDirty($pref, $bool)
    {
        $this->_setMask($pref, $bool, self::DIRTY);
    }

    /**
     * Returns the state of the "dirty" bit for the given preference.
     *
     * @param string $pref  The name of the preference to check.
     *
     * @return boolean  The boolean state of $pref's "dirty" bit.
     */
    public function isDirty($pref)
    {
        return $this->_getMask($pref, self::DIRTY);
    }

    /**
     * Modifies the "default" bit for the given preference.
     *
     * @param string $pref   The name of the preference to modify.
     * @param boolean $bool  The new boolean value for the "default" bit.
     */
    public function setDefault($pref, $bool)
    {
        $this->_setMask($pref, $bool, self::PREFS_DEFAULT);
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
        return empty($this->_prefs[$pref]['d'])
            ? ''
            : $this->_prefs[$pref]['d'];
    }

    /**
     * Determines if the current preference value is the default
     * value from prefs.php or a user defined value
     *
     * @param string $pref  The name of the preference to check.
     *
     * @return boolean  True if the preference is the application default
     *                  value.
     */
    public function isDefault($pref)
    {
        return $this->_getMask($pref, self::PREFS_DEFAULT);
    }

    /**
     * Sets the value for a given mask.
     *
     * @param string $pref   The name of the preference to modify.
     * @param boolean $bool  The new boolean value for the "default" bit.
     * @param integer $mask  The mask to add.
     */
    protected function _setMask($pref, $bool, $mask)
    {
        if (isset($this->_prefs[$pref]) &&
            ($bool != $this->_getMask($pref, $mask))) {
            if ($bool) {
                $this->_prefs[$pref]['m'] |= $mask;
            } else {
                $this->_prefs[$pref]['m'] &= ~$mask;
            }
        }
    }

    /**
     * Gets the boolean state for a given mask.
     *
     * @param string $pref   The name of the preference to modify.
     * @param integer $mask  The mask to get.
     *
     * @return boolean  The boolean state for the given mask.
     */
    protected function _getMask($pref, $mask)
    {
        return isset($this->_prefs[$pref]['m'])
            ? (bool)($this->_prefs[$pref]['m'] & $mask)
            : false;
    }

    /**
     * Returns the scope of the given preference.
     *
     * @param string $pref  The name of the preference to examine.
     *
     * @return string  The scope of the $pref.
     */
    protected function _getPreferenceScope($pref)
    {
        return $this->isShared($pref) ? 'horde' : $this->_scope;
    }

    /**
     * Retrieves preferences for the current scope + the 'horde'
     * scope.
     *
     * @param string $scope  Optional scope specifier - if not present the
     *                       current scope will be used.
     */
    public function retrieve($scope = null)
    {
        if (is_null($scope)) {
            $scope = $this->_scope;
        } else {
            $this->_scope = $scope;
        }

        $this->_loadScope('horde');
        if ($scope != 'horde') {
            $this->_loadScope($scope);
        }

        $this->_prefs = ($scope == 'horde')
            ? $this->_scopes['horde']
            : array_merge($this->_scopes['horde'], $this->_scopes[$scope]);
    }

    /**
     * Load a specific preference scope.
     */
    protected function _loadScope($scope)
    {
        // Return if we've already loaded these prefs.
        if (!empty($this->_scopes[$scope])) {
            return;
        }

        // Basic initialization so _something_ is always set.
        $this->_scopes[$scope] = array();

        // Always set defaults to pick up new default values, etc.
        $this->_setDefaults($scope);

        // Now check the prefs cache for existing values.
        if ($this->_cacheLookup($scope)) {
            return;
        }

        $this->_retrieve($scope);
        $this->_callHooks($scope);

        /* Update the session cache. */
        $this->_cacheUpdate($scope, array_keys($this->_scopes[$scope]));
    }

    /**
     * This function will be run at the end of every request as a shutdown
     * function (registered by the constructor).  All prefs with the
     * dirty bit set will be saved to the storage backend at this time; thus,
     * there is no need to manually call $prefs->store() every time a
     * preference is changed.
     */
    public function store()
    {
    }

    /**
     * TODO
     *
     * @throws Horde_Exception
     */
    protected function _retrieve()
    {
    }

    /**
     * This function provides common cleanup functions for all of the driver
     * implementations.
     *
     * @param boolean $all  Clean up all Horde preferences.
     */
    public function cleanup($all = false)
    {
        /* Perform a Horde-wide cleanup? */
        if ($all) {
            /* Destroy the contents of the preferences hash. */
            $this->_prefs = array();

            /* Destroy the contents of the preferences cache. */
            unset($this->_cache);
        } else {
            /* Remove this scope from the preferences cache, if it exists. */
            unset($this->_cache[$this->_scope]);
        }
    }

    /**
     * Clears all preferences from the backend.
     */
    public function clear()
    {
        $this->cleanup(true);
    }

    /**
     * Converts a value from the driver's charset to the specified charset.
     *
     * @param mixed $value     A value to convert.
     * @param string $charset  The charset to convert to.
     *
     * @return mixed  The converted value.
     */
    public function convertFromDriver($value, $charset)
    {
        return $value;
    }

    /**
     * Converts a value from the specified charset to the driver's charset.
     *
     * @param mixed $value     A value to convert.
     * @param string $charset  The charset to convert from.
     *
     * @return mixed  The converted value.
     */
    public function convertToDriver($value, $charset)
    {
        return $value;
    }

    /**
     * Return all "dirty" preferences across all scopes.
     *
     * @return array  The values for all dirty preferences, in a
     *                multi-dimensional array of scope => pref name =>
     *                pref values.
     */
    protected function _dirtyPrefs()
    {
        $dirty_prefs = array();

        foreach ($this->_scopes as $scope => $prefs) {
            foreach ($prefs as $pref_name => $pref) {
                if (isset($pref['m']) && ($pref['m'] & self::DIRTY)) {
                    $dirty_prefs[$scope][$pref_name] = $pref;
                }
            }
        }

        return $dirty_prefs;
    }

    /**
     * Updates the session-based preferences cache (if available).
     *
     * @param string $scope  The scope of the prefs being updated.
     * @param array $prefs   The preferences to update.
     */
    protected function _cacheUpdate($scope, $prefs)
    {
        if ($this->_caching && isset($this->_cache)) {
            /* Place each preference in the cache according to its
             * scope. */
            foreach ($prefs as $name) {
                if (isset($this->_scopes[$scope][$name])) {
                    $this->_cache[$scope][$name] = $this->_scopes[$scope][$name];
                }
            }
        }
    }

    /**
     * Tries to find the requested preferences in the cache. If they
     * exist, update the $_scopes hash with the cached values.
     *
     * @return boolean  True on success, false on failure.
     */
    protected function _cacheLookup($scope)
    {
        if ($this->_caching && isset($this->_cache[$scope])) {
            $this->_scopes[$scope] = $this->_cache[$scope];
            return true;
        }

        return false;
    }

    /**
     * Populates the $_scopes hash with new entries and externally
     * defined default values.
     *
     * @param string $scope  The scope to load defaults for.
     */
    protected function _setDefaults($scope)
    {
        /* Read the configuration file. The $_prefs array is assumed to hold
         * the default values. */
        try {
            $result = Horde::loadConfiguration('prefs.php', array('_prefs'), $scope);
            if (empty($result) || !isset($result['_prefs'])) {
                return;
            }
        } catch (Horde_Exception $e) {
            return;
        }

        foreach ($result['_prefs'] as $name => $pref) {
            if (!isset($pref['value'])) {
                continue;
            }

            $name = str_replace('.', '_', $name);

            $mask = 0;
            $mask &= ~self::DIRTY;
            $mask |= self::PREFS_DEFAULT;

            if (!empty($pref['locked'])) {
                $mask |= self::LOCKED;
            }

            if (empty($pref['shared'])) {
                $pref_scope = $scope;
            } else {
                $mask |= self::SHARED;
                $pref_scope = 'horde';
            }

            if (!empty($pref['shared']) &&
                isset($this->_scopes[$pref_scope][$name])) {
                // This is a shared preference that was already retrieved.
                $this->_scopes[$pref_scope][$name]['m'] = $mask & ~self::PREFS_DEFAULT;
                $this->_scopes[$pref_scope][$name]['d'] = $pref['value'];
            } else {
                $this->_scopes[$pref_scope][$name] = array(
                    'd' => $pref['value'],
                    'm' => $mask,
                    'v' => $pref['value']
                );
            }

            if (!empty($pref['hook'])) {
                $this->_hooks[$scope][$name] = $pref_scope;
            }
        }
    }

    /**
     * After preferences have been loaded, set any locked or empty
     * preferences that have hooks to the result of the hook.
     *
     * @param string $scope  The preferences scope to call hooks for.
     *
     * @throws Horde_Exception
     */
    protected function _callHooks($scope)
    {
        if (empty($this->_hooks[$scope])) {
            return;
        }

        foreach ($this->_hooks[$scope] as $name => $pref_scope) {
            if ($this->_scopes[$pref_scope][$name]['m'] & self::LOCKED ||
                empty($this->_scopes[$pref_scope][$name]['v']) ||
                $this->_scopes[$pref_scope][$name]['m'] & self::PREFS_DEFAULT) {

                try {
                    $val = Horde::callHook('prefs_hook_' . $name, array($this->_user), $scope);
                } catch (Horde_Exception_HookNotSet $e) {
                    continue;
                }

                if ($this->_scopes[$pref_scope][$name]['m'] & self::PREFS_DEFAULT) {
                    $this->_scopes[$pref_scope][$name]['v'] = $val;
                } else {
                    $this->_scopes[$pref_scope][$name]['v'] = $this->convertToDriver($val, Horde_Nls::getCharset());
                }
                if (!($this->_scopes[$pref_scope][$name]['m'] & self::LOCKED)) {
                    $this->_scopes[$pref_scope][$name]['m'] |= self::DIRTY;
                }
            }
        }
    }

}
