<?php
/**
 * The Horde_Core_Prefs class extends the base Horde_Prefs class by adding
 * support for the prefs.php configuration file and Horde hooks.
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
class Horde_Core_Prefs extends Horde_Prefs
{
    /**
     * Hash holding preferences with hook functions defined.
     *
     * @var array
     */
    protected $_hooks = array();

    /**
     */
    protected function _setValue($pref, $val, $convert = true)
    {
        if (!parent::_setValue($pref, $val, $convert)) {
            return false;
        }

        /* If this preference has a change hook, call it now. */
        try {
            Horde::callHook('prefs_change', array($pref), $this->_getPrefScope($pref));
        } catch (Horde_Exception_HookNotSet $e) {}

        return true;
    }

    /**
     * Populates the $_scopes hash with the default values as loaded from
     * an application's prefs.php file.
     */
    protected function _loadScopePre($scope)
    {
        /* Read the configuration file. The $_prefs array holds the default
         * values. */
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

            $mask = self::IS_DEFAULT;
            if (!empty($pref['locked'])) {
                $mask |= self::LOCKED;
            }

            $this->_scopes[$scope][$name] = array(
                'm' => $mask,
                'v' => $pref['value']
            );

            if (!empty($pref['hook'])) {
                $this->_hooks[$scope][] = $name;
            }
        }
    }

    /**
     * After preferences have been loaded, set any locked or empty
     * preferences that have hooks to the result of the hook.
     */
    protected function _loadScopePost($scope)
    {
        if (empty($this->_hooks[$scope])) {
            return;
        }

        foreach ($this->_hooks[$scope] as $name) {
            if ($this->isLocked($name) ||
                $this->isDefault($name) ||
                empty($this->_scopes[$scope][$name]['v'])) {
                try {
                    $val = Horde::callHook('prefs_init', array($name, $this->getUser()), $scope);
                } catch (Horde_Exception_HookNotSet $e) {
                    continue;
                }

                $this->_scopes[$scope][$name]['v'] = $this->isDefault($name)
                    ? $val
                    : $this->convertToDriver($val);

                if (!$this->_isLocked($name)) {
                    $this->setDirty($pref, true);
                }
            }
        }
    }

}
