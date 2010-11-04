<?php
/**
 * Preferences session storage implementation using Horde_Session.
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
class Horde_Core_Prefs_Storage_Session extends Horde_Prefs_Storage
{
    const SESS_KEY = 'prefs_session/';

    /**
     */
    public function get($scope)
    {
        global $session;

        return $session->exists('horde', self::SESS_KEY . $scope)
            ? $session->get('horde', self::SESS_KEY . $scope)
            : false;
    }

    /**
     */
    public function store($prefs)
    {
        foreach ($prefs as $scope => $vals) {
            if (($old_vals = $this->get($scope)) === false) {
                $old_vals = array();
            }
            $GLOBALS['session']->set('horde', self::SESS_KEY . $scope, array_merge($old_vals, $vals));
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        global $session;

        if (is_null($scope)) {
            $session->remove('horde', self::SESS_KEY);
        } elseif (is_null($pref)) {
            $session->remove('horde', self::SESS_KEY . $this->_scope);
        } elseif ((($vals = $this->get($scope)) !== false) &&
                  isset($vals[$pref])) {
            unset($vals[$pref]);
            $session->set('horde', self::SESS_KEY . $scope, $vals);
        }
    }

}
