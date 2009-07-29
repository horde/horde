<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_LoginTasks
 */
class IMP_LoginTasks_SystemTask_Upgrade extends Horde_LoginTasks_SystemTask
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
        IMP::initialize();

        /* IMP 4 upgrade: check for old, non-existent sort values.
         * See Bug #7296. */
        $sortby = $GLOBALS['prefs']->getValue('sortby');
        if ($sortby > 10) {
            $GLOBALS['prefs']->setValue('sortby', Horde_Imap_Client::SORT_ARRIVAL);
        }
    }

}
