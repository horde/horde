<?php
/**
 * Preferences storage implementation that loads the default values from
 * the configuration files.
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
class Horde_Core_Prefs_Storage_Configuration extends Horde_Prefs_Storage
{
    /**
     * The list of preference hooks defined.
     *
     * @var array
     */
    public $hooks = array();

    /**
     */
    public function get($scope_ob)
    {
        /* Read the configuration file.
         * Values are in the $_prefs array. */
        try {
            $result = Horde::loadConfiguration('prefs.php', array('_prefs'), $scope_ob->scope);
            if (empty($result) || !isset($result['_prefs'])) {
                return false;
            }
        } catch (Horde_Exception $e) {
            return false;
        }

        foreach ($result['_prefs'] as $name => $pref) {
            if (!isset($pref['value'])) {
                continue;
            }

            $scope_ob->set($name, $pref['value']);
            if (!empty($pref['locked'])) {
                $scope_ob->setLocked($name, true);
            }

            if (!empty($pref['hook'])) {
                $this->hooks[$scope_ob->scope][] = $name;
            }
        }

        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
        // Configuration files are read-only.
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        // Configuration files are read-only.
    }

}
