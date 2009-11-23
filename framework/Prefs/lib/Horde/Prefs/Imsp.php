<?php
/**
 * Preference storage implementation for an IMSP server.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Prefs
 */
class Horde_Prefs_Imsp extends Horde_Prefs
{
    /**
     * Handle for the IMSP server connection.
     *
     * @var Net_IMSP
     */
    protected $_imsp;

    /**
     * User password.
     *
     * @var string
     */
    protected $_password;

    /**
     * Boolean indicating whether or not we're connected to the IMSP server.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Holds the driver specific parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Retrieves the requested set of preferences from the IMSP server.
     *
     * @param string $scope  Scope specifier.
     *
     * @throws Horde_Exception
     */
    protected function _retrieve($scope)
    {
        /* Now connect to the IMSP server. */
        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if (empty($_SESSION['prefs_cache']['unavailable'])) {
                $_SESSION['prefs_cache']['unavailable'] = true;
                $GLOBALS['notification']->push(_("The preferences backend is currently unavailable and your preferences have not been loaded. You may continue to use the system with default settings."));
            }
            return;
        }

        $prefs = $this->_imsp->get($scope . '.*');
        if ($prefs instanceof PEAR_Error) {
            Horde::logMessage($prefs, __FILE__, __LINE__, PEAR_LOG_ERR);
            return;
        }

        foreach ($prefs as $name => $val) {
            $name = str_replace($scope . '.', '', $name);
            if ($val != '-') {
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
    }

    /**
     * Stores all dirty prefs to IMSP server.
     */
    public function store()
    {
        // Get the list of preferences that have changed. If there are
        // none, no need to hit the backend.
        $dirty_prefs = $this->_dirtyPrefs();
        if (!$dirty_prefs) {
            return;
        }

        $this->_connect();

        foreach ($dirty_prefs as $scope => $prefs) {
            foreach ($prefs as $name => $pref) {
                // Don't store locked preferences.
                if ($this->_scopes[$scope][$name]['m'] & self::LOCKED) {
                    continue;
                }

                $value = $pref['v'];
                if (empty($value)) {
                    $value = '-';
                }

                $result = $this->_imsp->set($scope . '.' . $name, $value);
                if ($result instanceof PEAR_Error) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return;
                }

                // Clean the pref since it was just saved.
                $this->_scopes[$scope][$name]['m'] &= ~self::DIRTY;
            }

            // Update the cache for this scope.
            $this->_cacheUpdate($scope, array_keys($prefs));
        }
    }

    /**
     * Attempts to set up a connection to the IMSP server.
     *
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        $this->_params['username'] = preg_match('/(^.*)@/', $this->_user, $matches)
            ? $matches[1]
            : $this->_user;
        $this->_params['password'] = $this->_password;

        if (isset($this->_params['socket'])) {
            $this->_params['socket'] = $params['socket'] . 'imsp_' . $this->_params['username'] . '.sck';
        }

        $this->_imsp = Net_IMSP::factory('Options', $this->_params);
        $result = $this->_imsp->init();
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($result);
        }

        $this->_imsp->setLogger($GLOBALS['conf']['log']);
        $this->_connected = true;
    }

}
