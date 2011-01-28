<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Turba
 */
class Turba_LoginTasks_SystemTask_UpgradeFromTurba2 extends Horde_LoginTasks_SystemTask
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
        $this->_upgradeAbookPrefs();
    }

    /**
     * Upgrade to the new addressbook preferences.
     */
    protected function _upgradeAbookPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('addressbooks')) {
            $abooks = $prefs->getValue('addressbooks');
            if (!is_array(json_decode($abooks))) {
                $abooks = @explode("\n", $abooks);
                if (empty($abooks)) {
                    $abooks = array();
                }

                return $prefs->setValue('addressbooks', json_encode($abooks));
            }
        }
    }

}
