<?php
/**
 * Preferences storage implementation for PHP's session implementation.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Horde_Prefs
 */
class Horde_Prefs_Session extends Horde_Prefs
{
    /**
     * Retrieves the requested set of preferences from the current session.
     *
     * @param string $scope  Scope specifier.
     */
    protected function _retrieve($scope)
    {
        if (isset($_SESSION['horde_prefs'][$scope])) {
            $this->_scopes[$scope] = $_SESSION['horde_prefs'][$scope];
        }
    }

    /**
     * Stores preferences in the current session.
     */
    public function store()
    {
        // Create and register the preferences array, if necessary.
        if (!isset($_SESSION['horde_prefs'])) {
            $_SESSION['horde_prefs'] = array();
        }

        // Copy the current preferences into the session variable.
        foreach ($this->_scopes as $scope => $prefs) {
            $pref_keys = array_keys($prefs);
            foreach ($pref_keys as $pref_name) {
                // Clean the pref since it was just saved.
                $prefs[$pref_name]['m'] &= ~Horde_Prefs::DIRTY;
            }

            $_SESSION['horde_prefs'][$scope] = $prefs;
        }
    }

    /**
     * Perform cleanup operations.
     *
     * @param boolean $all  Cleanup all Horde preferences.
     */
    public function cleanup($all = false)
    {
        // Perform a Horde-wide cleanup?
        if ($all) {
            unset($_SESSION['horde_prefs']);
        } else {
            unset($_SESSION['horde_prefs'][$this->_scope]);
            $_SESSION['horde_prefs']['_filled'] = false;
        }

        parent::cleanup($all);
    }

}
