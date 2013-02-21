<?php
/**
 * Copyright 2009-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2009-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Login system task for performing periodical garbage collection.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        global $conf, $injector;

        /* These require mail server authentication. */
        try {
            /* Purge non-existent nav_poll entries. */
            $injector->getInstance('IMP_Imap_Tree')->prunePollList();

            /* Purge non-existent search sorts. */
            $injector->getInstance('IMP_Prefs_Sort')->gc();
        } catch (Exception $e) {}

        /* Do garbage collection on sentmail entries. */
        $injector->getInstance('IMP_Sentmail')->gc();

        /* Do garbage collection on compose VFS data. */
        if ($conf['compose']['use_vfs']) {
            try {
                $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create();
                Horde_Vfs_Gc::gc($vfs, IMP_Compose::VFS_ATTACH_PATH, 86400);
            } catch (Horde_Vfs_Exception $e) {}
        }
    }

}
