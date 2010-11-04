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

    /** Preference value is the application default.
     *  DEFAULT is a reserved PHP constant. */
    const IS_DEFAULT = 2;

    /* The default scope name. */
    const DEFAULT_SCOPE = 'horde';

    /**
     * Caching object.
     *
     * @var Horde_Prefs_Storage
     */
    protected $_cache;

    /**
     * List of dirty prefs.
     *
     * @var array
     */
    protected $_dirty = array();

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
     * to differentiate between sets of preferences.  By default, preferences
     * belong to the "global" (Horde) scope.
     *
     * @var string
     */
    protected $_scope = self::DEFAULT_SCOPE;

    /**
     * Preferences list.  Stored by scope name.  Each preference has the
     * following format:
     * <pre>
     * [pref_name] => array(
     *     [d] => (string) Default value
     *     [m] => (integer) Pref mask
     *     [v] => (string) Current pref value
     * )
     * </pre>
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
     * REQUIRED:
     * ---------
     * charset - (string) Default charset.
     *
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
     * user - (string) The name of the user who owns this set of preferences.
     *        DEFAULT: NONE
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($scope, $storage = null, array $opts = array())
    {
        if (!isset($opts['charset'])) {
            throw new InvalidArgumentException(__CLASS__ . ': Missing charset parameter.');
        }

        $this->_opts = array_merge($this->_opts, $opts);

        $default = __CLASS__ . '_Storage_Null';

        $this->_cache = isset($this->_opts['cache'])
            ? $this->_opts['cache']
            : new $default($this->getUser());
        $this->_scope = $scope;
        if (is_null($storage)) {
            $this->_storage = array(new $default($this->getUser()));
        } else {
            if (!is_array($storage)) {
                $storage = array($storage);
            }
            $this->_storage = $storage;
        }

        register_shutdown_function(array($this, 'store'));

        $this->retrieve($scope);
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
        $scope = $this->_getPrefScope($pref);
        unset(
            $this->_dirty[$scope][$pref],
            $this->_scopes[$scope][$pref]
        );

        foreach ($this->_storage as $storage) {
            try {
                $storage->remove($scope, $pref);
            } catch (Horde_Prefs_Exception $e) {
                // TODO: logging
            }
        }

        try {
            $this->_cache->remove($scope, $pref);
        } catch (Horde_Prefs_Exception $e) {
            // TODO: logging
        }
    }

    /**
     * Sets the given preference to the specified value if the preference is
     * modifiable.
     *
     * @param string $pref      The name of the preference to modify.
     * @param string $val       The new value for this preference.
     * @param boolean $convert  If true the preference value gets converted
     *                          from the current charset to the backend's
     *                          charset.
     *
     * @return boolean  True if the value was successfully set, false on a
     *                  failure.
     * @throws Horde_Prefs_Exception
     */
    public function setValue($pref, $val, $convert = true)
    {
        $scope = $this->_getPrefScope($pref);

        /* Exit early if this preference is locked or doesn't exist. */
        if (!isset($this->_scopes[$scope][$pref]) || $this->isLocked($pref)) {
            return false;
        }

        if (!$this->_setValue($pref, $val, $convert)) {
            return false;
        }

        $this->_cache->store(array(
            $scope => array(
                $pref => $this->_scopes[$scope][$pref]
            )
        ));

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
     * Sets the given preferences ($pref) to the specified value
     * ($val), whether or not the preference is user-modifiable, unset
     * the default bit, and set the dirty bit.
     *
     * @param string $pref      The name of the preference to modify.
     * @param string $val       The new value for this preference.
     * @param boolean $convert  If true the preference value gets converted
     *                          from the current charset to the backend's
     *                          charset.
     *
     * @return boolean  True if the value was successfully set, false on a
     *                  failure.
     */
    protected function _setValue($pref, $val, $convert = true)
    {
        if ($convert) {
            $val = $this->convertToDriver($val);
        }

        $scope = $this->_getPrefScope($pref);

        // If the preference's value is already equal to $val, don't
        // bother changing it. Changing it would set the "dirty" bit,
        // causing an unnecessary update later.
        if (isset($this->_scopes[$scope][$pref]) &&
            (($this->_scopes[$scope][$pref]['v'] == $val) &&
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
        $ptr = &$this->_scopes[$scope][$pref];
        if (empty($ptr['m'])) {
            $ptr['m'] = 0;
        }
        if (!isset($ptr['d'])) {
            $ptr['d'] = $ptr['v'];
        }
        $ptr['v'] = $val;
        $this->setDefault($pref, false);
        $this->setDirty($pref, true);

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
        $scope = $this->_getPrefScope($pref);

        $value = isset($this->_scopes[$scope][$pref]['v'])
            ? $this->_scopes[$scope][$pref]['v']
            : null;

        if ($convert &&
            !is_null($value) &&
            !$this->isDefault($pref)) {
            /* Default values have the current UI charset.
             * Stored values have the backend charset. */
            $value = $this->convertFromDriver($value);
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
     * @param string $pref   The name of the preference to modify.
     * @param boolean $bool  The new boolean value for the "dirty" bit.
     */
    public function setDirty($pref, $bool)
    {
        $scope = $this->_getPrefScope($pref);

        if ($bool) {
            $this->_dirty[$scope][$pref] = $this->_scopes[$scope][$pref];
        } else {
            unset($this->_dirty[$scope][$pref]);
        }
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
        $scope = $this->_getPrefScope($pref);
        return isset($this->_dirty[$scope][$pref]);
    }

    /**
     * Modifies the "default" bit for the given preference.
     *
     * @param string $pref   The name of the preference to modify.
     * @param boolean $bool  The new boolean value for the "default" bit.
     */
    public function setDefault($pref, $bool)
    {
        $this->_setMask($pref, $bool, self::IS_DEFAULT);
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
        $scope = $this->_getPrefScope($pref);

        return isset($this->_scopes[$scope][$pref]['d'])
            ? $this->_scopes[$scope][$pref]['d']
            : '';
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
        return $this->_getMask($pref, self::IS_DEFAULT);
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
        $scope = $this->_getPrefScope($pref);

        if (isset($this->_scopes[$scope][$pref]) &&
            ($bool != $this->_getMask($pref, $mask))) {
            if ($bool) {
                $this->_scopes[$scope][$pref]['m'] |= $mask;
            } else {
                $this->_scopes[$scope][$pref]['m'] &= ~$mask;
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
        $scope = $this->_getPrefScope($pref);

        return isset($this->_scopes[$scope][$pref]['m'])
            ? (bool)($this->_scopes[$scope][$pref]['m'] & $mask)
            : false;
    }

    /**
     * Returns the scope of the given preference.
     *
     * @param string $pref  The name of the preference to examine.
     *
     * @return string  The scope of the $pref.
     */
    protected function _getPrefScope($pref)
    {
        return (isset($this->_scopes[$this->_scope][$pref]) || !isset($this->_scopes[self::DEFAULT_SCOPE][$pref]))
            ? $this->_scope
            : self::DEFAULT_SCOPE;
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
            $this->setScope($scope);
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

        // Basic initialization so _something_ is always set.
        $this->_scopes[$scope] = array();

        // Now check the prefs cache for existing values.
        try {
            if (($cached = $this->_cache->get($scope)) !== false) {
                $this->_scopes[$scope] = $cached;
                return;
            }
        } catch (Horde_Prefs_Exception $e) {}

        $this->_loadScopePre($scope);

        foreach ($this->_storage as $storage) {
            if (($prefs = $storage->get($scope)) !== false) {
                foreach ($prefs as $name => $val) {
                    if (isset($this->_scopes[$scope][$name])) {
                        if ($this->isDefault($name)) {
                            $this->_scopes[$scope][$name]['d'] = $this->_scopes[$scope][$name]['v'];
                        }
                    } else {
                        $this->_scopes[$scope][$name] = array(
                            'm' => 0
                        );
                    }
                    $this->_scopes[$scope][$name]['v'] = $val;
                    $this->setDefault($name, false);
                }
            }
        }

        $this->_loadScopePost($scope);

        /* Update the cache. */
        $this->_cache->store(array($scope => $this->_scopes[$scope]));
    }

    /**
     * Actions to perform before a scope is loaded from storage.
     *
     * @param string $scope  The scope to load.
     */
    protected function _loadScopePre($scope)
    {
    }

    /**
     * Actions to perform after a scope is loaded from storage.
     *
     * @param string $scope  The loaded scope.
     */
    protected function _loadScopePost($scope)
    {
    }

    /**
     * This function will be run at the end of every request as a shutdown
     * function (registered by the constructor).  All prefs with the
     * dirty bit set will be saved to the storage backend.
     */
    public function store()
    {
        if (!empty($this->_dirty)) {
            foreach ($this->_storage as $storage) {
                try {
                    $storage->store($this->_dirty);

                    /* Clear the dirty flag. */
                    foreach ($this->_dirty as $k => $v) {
                        foreach (array_keys($v) as $name) {
                            $this->setDirty($name, false);
                        }
                    }
                } catch (Horde_Prefs_Exception $e) {}
            }
        }
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
            $this->_dirty = $this->_scopes = array();

            /* Destroy the contents of the preferences cache. */
            try {
                $this->_cache->remove();
            } catch (Horde_Prefs_Exception $e) {}
        } else {
            $scope = $this->getScope();

            $this->_dirty[$scope] = $this->_scopes[$scope] = array();
            /* Remove this scope from the preferences cache. */
            try {
                $this->_cache->remove($scope);
            } catch (Horde_Prefs_Exception $e) {}
        }
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
