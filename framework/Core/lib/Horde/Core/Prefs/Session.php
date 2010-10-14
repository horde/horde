<?php
/**
 * Preferences storage implementation using Horde_Session.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Prefs_Session extends Horde_Prefs
{
    /**
     * Retrieves the requested set of preferences from the current session.
     *
     * @param string $scope  Scope specifier.
     */
    protected function _retrieve($scope)
    {
        global $session;

        if (isset($session['horde:prefs_session/' . $scope])) {
            $this->_scopes[$scope] = $session['horde:prefs_session/' . $scope];
        }
    }

    /**
     * Stores preferences in the current session.
     */
    public function store()
    {
        // Copy the current preferences into the session variable.
        foreach ($this->_scopes as $scope => $prefs) {
            foreach (array_keys($prefs) as $pref_name) {
                // Clean the pref since it was just saved.
                $prefs[$pref_name]['m'] &= ~Horde_Prefs::DIRTY;
            }

            $session['horde:prefs_session/' . $scope] = $prefs;
        }
    }

    /**
     * Perform cleanup operations.
     *
     * @param boolean $all  Cleanup all Horde preferences.
     */
    public function cleanup($all = false)
    {
        global $session;

        // Perform a Horde-wide cleanup?
        if ($all) {
            unset($session['horde:prefs_session/']);
        } else {
            unset($session['horde:prefs_session/' . $this->_scope]);
        }

        parent::cleanup($all);
    }

}
