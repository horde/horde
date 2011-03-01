<?php
/**
 * Login system task for performing periodical garbage collection.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_LoginTasks_SystemTask_GarbageCollection extends Horde_LoginTasks_SystemTask
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::WEEKLY;

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        /* Purge non-existent nav_poll entries. */
        $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->prunePollList();

        /* Do garbage collection on sentmail entries. */
        $GLOBALS['injector']->getInstance('IMP_Sentmail')->gc();

        /* Do garbage collection on compose VFS data. */
        if ($GLOBALS['conf']['compose']['use_vfs']) {
            try {
                $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
                VFS_GC::gc($vfs, IMP_Compose::VFS_ATTACH_PATH, 86400);
            } catch (VFS_Exception $e) {}
        }

        /* Purge non-existent search sorts. */
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        $update = false;
        $sortpref = @unserialize($GLOBALS['prefs']->getValue('sortpref'));

        foreach (array_keys($sortpref) as $key) {
            if ($imp_search->isSearchMbox($key) && !$imp_search[$key]) {
                unset($sortpref[$key]);
                $update = true;
            }
        }

        if ($update) {
            $GLOBALS['prefs']->setValue('sortpref', serialize($sortpref));
        }
    }

}
