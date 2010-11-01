<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Gollem
 */
class Gollem_LoginTasks_SystemTask_UpgradeFromGollem1 extends Horde_LoginTasks_SystemTask
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
        $this->_upgradePrefs();
    }

    /**
     * Upgrade to the new preferences.
     */
    protected function _upgradePrefs()
    {
        global $prefs;

        if (!$prefs->isDefault('columns')) {
            $cols = $prefs->getValue('columns');
            if (!is_array(json_decode($cols))) {
                $prefs->setValue('columns', json_encode(explode("\t", $cols)));
            }
        }
    }

}
