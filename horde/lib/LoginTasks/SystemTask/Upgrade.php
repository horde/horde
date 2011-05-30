<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */
class Horde_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_versions = array(
        '4.0'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '4.0':
            $this->_clearCache();
            $this->_upgradePortal();
            $this->_upgradePrefs();
            break;
        }
    }

    /**
     * Clear the existing cache.
     */
    protected function _clearCache()
    {
        try {
            $GLOBALS['injector']->getInstance('Horde_Cache')->clear();
        } catch (Exception $e) {}
    }

    /**
     * Upgrade portal preferences.
     */
    protected function _upgradePortal()
    {
        $bu = new Horde_Core_Block_Upgrade();
        $bu->upgrade('portal_layout');
    }

    /**
     * Upgrade to the new preferences storage format.
     */
    protected function _upgradePrefs()
    {
        $upgrade_prefs = array(
            'identities'
        );

        $GLOBALS['injector']->getInstance('Horde_Core_Prefs_Storage_Upgrade')->upgradeSerialized($GLOBALS['prefs'], $upgrade_prefs);
    }

}
