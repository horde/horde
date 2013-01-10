<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */
class Horde_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_versions = array(
        '4.0',
        '4.0.12',
        '5.0.1'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '4.0':
            $this->_upgradePortal();
            $this->_upgradePrefs();
            break;

        case '4.0.12':
            $this->_replaceWeatherBlock();
            break;

        case '5.0.1':
            $this->_upgradeSendingCharsetPref();
            break;
        }
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

    protected function _replaceWeatherBlock()
    {
        $col = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_BlockCollection')
            ->create(array('horde'));
        $m = $col->getLayoutManager();
        $layout = $col->getLayout();
        foreach ($layout as $r => $cur_row) {
            foreach ($cur_row as $c => &$cur_col) {
                if (isset($cur_col['app']) &&
                    $cur_col['app'] == 'horde' &&
                    is_array($cur_col['params']) &&
                    Horde_String::lower($cur_col['params']['type2']) == 'horde_block_weatherdotcom') {

                    $m->handle('removeBlock', $r, $c);
                }
            }
        }
        if ($m->updated()) {
            $GLOBALS['prefs']->setValue('portal_layout', $m->serialize());
        }
    }

    /**
     * In H5, default to UTF-8 for sending_charset.
     */
    protected function _upgradeSendingCharsetPref()
    {
        $GLOBALS['prefs']->remove('sending_charset');
    }

}
