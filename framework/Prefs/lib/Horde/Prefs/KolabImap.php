<?php
/**
 * Preferences storage implementation for a Kolab IMAP server.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <p@rdus.de>
 * @package Horde_Prefs
 */
class Horde_Prefs_KolabImap extends Horde_Prefs
{
    /**
     * ID of the config default share
     *
     * @var string
     */
    protected $_share;

    /**
     * Handle for the current Kolab connection.
     *
     * @var Kolab
     */
    protected $_connection;

    /**
     * Opens a connection to the Kolab server.
     *
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        if (isset($this->_connection)) {
            return;
        }

        $shares = Horde_Share::singleton('h-prefs');
        $default = $shares->getDefaultShare();
        if ($default instanceof PEAR_Error) {
            Horde::logMessage($default, 'ERR');
            throw new Horde_Exception_Prior($default);
        }
        $this->_share = $default->getName();

        require_once 'Horde/Kolab.php';
        $connection = new Kolab('h-prefs');
        if ($connection instanceof PEAR_Error) {
            Horde::logMessage($connection, 'ERR');
            throw new Horde_Exception_Prior($connection);
        }

        $result = $this->_connection->open($this->_share, 1);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Exception_Prior($result);
        }

        $this->_connection = $connection;
    }

    /**
     * Retrieves the requested set of preferences from the user's config folder.
     *
     * @param string $scope  Scope specifier.
     *
     * @throws Horde_Exception
     */
    protected function _retrieve($scope)
    {
        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if (empty($_SESSION['prefs_cache']['unavailable'])) {
                $_SESSION['prefs_cache']['unavailable'] = true;
                if (isset($GLOBALS['notification'])) {
                    $GLOBALS['notification']->push(_("The preferences backend is currently unavailable and your preferences have not been loaded. You may continue to use the system with default settings."));
                }
            }
            return;
        }

        try {
            $pref = $this->_getPref($scope);
        } catch (Horde_Exception $e) {
            return;
        }

        if (is_null($pref)) {
            /* No preferences saved yet */
            return;
        }

        foreach ($pref['pref'] as $prefstr) {
            // If the string doesn't contain a colon delimiter, skip it.
            if (strpos($prefstr, ':') === false) {
                continue;
            }

            // Split the string into its name:value components.
            list($name, $val) = explode(':', $prefstr, 2);
            if (isset($this->_scopes[$scope][$name])) {
                $this->_scopes[$scope][$name]['v'] = base64_decode($val);
                $this->_scopes[$scope][$name]['m'] &= ~self::PREFS_DEFAULT;
            } else {
                // This is a shared preference.
                $this->_scopes[$scope][$name] = array('v' => base64_decode($val),
                                                      'm' => 0,
                                                      'd' => null);
            }
        }
    }

    /**
     * Retrieves the requested preference from the user's config folder.
     *
     * @param string $scope  Scope specifier.
     *
     * @return array  The preference value.
     * @throws Horde_Exception
     */
    protected function _getPref($scope)
    {
        $this->_connect();

        $prefs = $this->_connection->getObjects();
        if ($prefs instanceof PEAR_Error) {
            Horde::logMessage($prefs, 'ERR');
            throw new Horde_Exception_Prior($prefs);
        }

        foreach ($prefs as $pref) {
            if ($pref['application'] == $scope) {
                return $pref;
            }
        }

        return null;
    }

    /**
     * Stores preferences to the Kolab server.
     *
     * @throws Horde_Exception
     */
    public function store()
    {
        // Get the list of preferences that have changed. If there are
        // none, no need to hit the backend.
        $dirty_prefs = $this->_dirtyPrefs();
        if (!$dirty_prefs) {
            return;
        }
        $dirty_scopes = array_keys($dirty_prefs);

        $this->_connect();

        // Build a hash of the preferences and their values that need
        // to be stored on the IMAP server. Because we have to update
        // all of the values of a multi-value entry wholesale, we
        // can't just pick out the dirty preferences; we must update
        // every scope that has dirty preferences.
        foreach ($dirty_scopes as $scope) {
            $new_values = array();
            foreach ($this->_scopes[$scope] as $name => $pref) {
                // Don't store locked preferences.
                if (!($pref['m'] & self::LOCKED)) {
                    $new_values[] = $name . ':' . base64_encode($pref['v']);
                }
            }

            try {
                $pref = $this->_getPref($scope);
            } catch (Horde_Exception $e) {
                return;
            }

            if (is_null($pref)) {
                $old_uid = null;
                $prefs_uid = $this->_connection->_storage->generateUID();
            } else {
                $old_uid = $pref['uid'];
                $prefs_uid = $pref['uid'];
            }

            $object = array(
                'uid' => $prefs_uid,
                'application' => $scope,
                'pref' => $new_values
            );

            $result = $this->_connection->_storage->save($object, $old_uid);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return;
            }
        }

        // Clean the preferences since they were just saved.
        foreach ($dirty_prefs as $scope => $prefs) {
            foreach ($prefs as $name => $pref) {
                $this->_scopes[$scope][$name]['m'] &= ~_PREF_DIRTY;
            }

            // Update the cache for this scope.
            $this->_cacheUpdate($scope, array_keys($prefs));
        }
    }

    /**
     * Clears all preferences from the kolab_imap backend.
     */
    public function clear()
    {
        return $this->_connection->deleteAll();
    }

}
