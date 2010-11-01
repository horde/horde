<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde
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
        $this->_upgradeIdentityPrefs();
    }

    /**
     * Upgrade to the new identity preferences.
     */
    protected function _upgradeIdentityPrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('identities') &&
            (!($this->_identities = @unserialize($prefs->getValue('identities', false))))) {
            $identities = @unserialize($prefs->getValue('identities'));
            if (!is_array($identities)) {
                $identities = $prefs->getDefault('identities');
            }
            $prefs->setValue('identities', serialize($identities), false);
        }
    }

}
