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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Prefs
 */
class Horde_Prefs implements ArrayAccess
{
    /** Preference is administratively locked. */
    const LOCKED = 1;

    /** Preference value has been changed. */
    const DIRTY = 4;

    /** Preference value is the application default.
     *  DEFAULT is a reserved PHP constant. */
    const PREFS_DEFAULT = 8;

    /**
     * Connection parameters.
     *
     * @var array
     */
    protected $_params = array();

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
     * General library options.
     *
     * @var array
     */
    protected $_opts = array(
        'cache' => 'Horde_Prefs_Cache_Null',
        'logger' => null,
        'password' => '',
        'sizecallback' => null,
        'user' => ''
    );

    /**
     * Caching object.
     *
     * @var Horde_Prefs_Cache
     */
    protected $_cache;

    /**
     * Hash holding preferences with hook functions defined.
     *
     * @var array
     */
    protected $_hooks = array();

    /**
     * Translation provider.
     *
     * @var Horde_Translation
     */
    protected $_dict;

    /**
     * List of dirty prefs.
     *
     * @var array
     */
    protected $_dirty = array();

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  Either a driver name, or the full class name to
     *                        use (class must extend Horde_Auth_Base).
     * @param string $scope   The scope for this set of preferences.
     * @param array $opts     Additional confguration options:
     * <pre>
     * REQUIRED:
     * ---------
     * charset - (string) Default charset.
     * OPTIONAL:
     * ---------
     * cache - (string) The class name defining the cache driver to use.
     *         DEFAULT: Caching is not used
     * logger - (Horde_Log_Logger) Logging object.
     *          DEFAULT: NONE
     * password - (string) The password associated with 'user'.
     *            DEFAULT: NONE
     * sizecallback - (callback) If set, called when setting a value in the
     *                backend.
     *                DEFAULT: NONE
     * translation - (object) A translation handler implementing
     *               Horde_Translation.
     * user - (string) The name of the user who owns this set of preferences.
     *        DEFAULT: NONE
     * </pre>
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Horde_Prefs  The newly created concrete instance.
     * @throws Horde_Prefs_Exception
     */
    static public function factory($driver, $scope, array $opts = array(),
                                   array $params = array())
    {
        /* Base drivers (in Auth/ directory). */
        $class = __CLASS__ . '_' . $driver;
        if (!class_exists($class)) {
            /* Explicit class name, */
            $class = $driver;
            if (!class_exists($class)) {
                throw new Horde_Prefs_Exception(__CLASS__ . ': class definition not found - ' . $class);
            }
        }

        $prefs = new $class($scope, $opts, $params);
        $prefs->retrieve($scope);

        return $prefs;
    }

    /**
     * Constructor.
     *
     * @param string $scope  The scope for this set of preferences.
     * @param array $opts    See factory() for list of options.
     * @param array $params  A hash containing any additional configuration
     *                       or connection parameters a subclass might need.
     *
     * @throws InvalidArgumentException
     */
    protected function __construct($scope, $opts, $params)
    {
        foreach (array('charset') as $val) {
            if (!isset($opts[$val])) {
                throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
            }
        }

        $this->_opts = array_merge($this->_opts, $opts);
        $this->_params = $params;
        $this->_scope = $scope;

        $this->_cache = new $this->_opts['cache']($this->getUser());
        $this->_dict = isset($this->_opts['translation'])
            ? $this->_opts['translation']
            : new Horde_Translation_Gettext('Horde_Prefs', dirname(__FILE__) . '/../../locale');

        register_shutdown_function(array($this, 'store'));
    }

    /**
     * Returns the charset used by the concrete preference backend.
     *
     * @return string  The preference backend's charset.
     */
    public function getCharset()
    {
        return $this->_opts['charset'];
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
        // FIXME not updated yet - not removed from backend.
        $scope = $this->_getPreferenceScope($pref);
        unset($this->_dirty[$scope][$pref], $this->_prefs[$pref]);
        $this->_cache->clear($scope, $pref);
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

        if ($result && $this->isDirty($pref)) {
            $scope = $this->_getPreferenceScope($pref);
            $this->_cache->update($scope, array(
                $pref => $this->_scopes[$scope][$pref]
            ));

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
        if ($convert) {
            $val = $this->convertToDriver($val);
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
        if ($this->_opts['sizecallback'] &&
            call_user_func($this->_opts['sizecallback'], $pref, strlen($val))) {
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

        if ($this->_opts['logger']) {
            $this->_opts['logger']->log(__CLASS__ . ': Storing preference value (' . $pref . ')', 'DEBUG');
        }

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
                    ? $this->_prefs[$pref]['v']
                    : $this->convertFromDriver($this->_prefs[$pref]['v']);
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
     * Modifies the "dirty" bit for the given preference.
     *
     * @param string $pref      The name of the preference to modify.
     * @param boolean $bool     The new boolean value for the "dirty" bit.
     */
    public function setDirty($pref, $bool)
    {
        if ($bool) {
            $this->_dirty[$this->_scope][$pref] = $this->_prefs[$pref];
        } else {
            unset($this->_dirty[$this->_scope][$pref]);
        }

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
        return $this->getScope();
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
            $scope = $this->getScope();
        } else {
            $this->setScope($scope);
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
        if (($cached = $this->_cache->get($scope)) !== false) {
            $this->_scopes[$scope] = $cached;
            return;
        }

        $this->_retrieve($scope);
        $this->_callHooks($scope);

        /* Update the cache. */
        $this->_cache->update($scope, $this->_scopes[$scope]);
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
            $this->_dirty = $this->_prefs = array();

            /* Destroy the contents of the preferences cache. */
            $this->_cache->clear();
        } else {
            /* Remove this scope from the preferences cache. */
            $this->_cache->clear($this->getScope());
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
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    public function convertFromDriver($value)
    {
        return is_bool($value)
            ? $value
            : Horde_String::convertCharset($value, $this->getCharset(), 'UTF-8');
    }

    /**
     * Converts a value from the specified charset to the driver's charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    public function convertToDriver($value)
    {
        return is_bool($value)
            ? $value
            : Horde_String::convertCharset($value, 'UTF-8', $this->getCharset());
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

            $this->_scopes[$scope][$name] = array(
                'd' => $pref['value'],
                'm' => $mask,
                'v' => $pref['value']
            );

            if (!empty($pref['hook'])) {
                $this->_hooks[$scope][$name] = $scope;
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
                    $val = Horde::callHook('prefs_hook_' . $name, array($this->getUser()), $scope);
                } catch (Horde_Exception_HookNotSet $e) {
                    continue;
                }

                if ($this->_scopes[$pref_scope][$name]['m'] & self::PREFS_DEFAULT) {
                    $this->_scopes[$pref_scope][$name]['v'] = $val;
                } else {
                    $this->_scopes[$pref_scope][$name]['v'] = $this->convertToDriver($val);
                }
                if (!($this->_scopes[$pref_scope][$name]['m'] & self::LOCKED)) {
                    $this->_scopes[$pref_scope][$name]['m'] |= self::DIRTY;
                }
            }
        }
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
