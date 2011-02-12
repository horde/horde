<?php
/**
 * Login system task for automated upgrade tasks from Horde 3.
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
class Horde_LoginTasks_SystemTask_UpgradeFromHorde3 extends Horde_LoginTasks_SystemTask
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::ONCE;

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        $this->_upgradePortal();
        $this->_upgradePrefs();
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
