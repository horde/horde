<?php
/**
 * Preferences storage implementation that adds support for Horde hooks to
 * manipulate preference values.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Prefs_Storage_Hooks extends Horde_Prefs_Storage_Base
{
    /**
     */
    public function get($scope_ob)
    {
        $conf_ob = $this->_params['conf_ob'];

        if (empty($conf_ob->hooks[$scope_ob->scope])) {
            return $scope_ob;
        }

        foreach ($conf_ob->hooks[$scope_ob->scope] as $name) {
            try {
                $scope_ob->set($name, Horde::callHook('prefs_init', array($name, $scope_ob->get($name), strlen($this->_params['user']) ? $this->_params['user'] : null, $scope_ob), $scope_ob->scope));
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
        // Hooks are read-only.
    }

    /**
     */
    public function onChange($scope, $pref)
    {
        try {
            Horde::callHook('prefs_change', array($pref), $scope);
        } catch (Horde_Exception_HookNotSet $e) {}
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        // Hooks are read-only.
    }

}
