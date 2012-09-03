<?php
/**
 * Login tasks module that purges completed tasks.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class Nag_LoginTasks_Task_PurgeCompleted extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($this->interval = $GLOBALS['prefs']->getValue('purge_completed_interval')) {
            if ($GLOBALS['prefs']->isLocked('purge_completed_interval')) {
                $this->display = Horde_LoginTasks::DISPLAY_NONE;
            }
        } else {
            $this->active = false;
        }
    }

    /**
     * Purge completed tasks that were completed before the configured date.
     *
     * @return boolean  Whether any messages were purged from the mailbox.
     */
    public function execute()
    {
        global $injector, $prefs;

        /* Get the current UNIX timestamp minus the number of days specified
         * in 'purge_completed_keep'.  If a message has a timestamp prior to
         * this value, it will be deleted. */
        $del_time = new Horde_Date(time() - ($prefs->getValue('purge_completed_keep') * 86400));
        $del_time = $del_time->timestamp();
        $tasklists = Nag::listTasklists(true, Horde_Perms::DELETE, false);
        $tasks = Nag::listTasks(array(
            'completed' => Nag::VIEW_COMPLETE,
            'tasklists' => array_keys($tasklists))
        );
        $storage = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Driver')
            ->create();
        $count = 0;
        $tasks->reset();
        while ($task = $tasks->each()) {
            if (($task->completed_date) && $task->completed_date < $del_time) {
                try {
                    $storage->delete($task->id);
                    ++$count;
                } catch (Nag_Exception $e) {
                    Horde::logMessage($e->getMessage(), 'ERR');
                }
            }
        }

        $GLOBALS['notification']->push(
            sprintf(ngettext("Purging %d completed task.", "Purging %d completed tasks.", $count), $count), 'horde.message');

        return true;
    }

    /**
     * Return information for the login task.
     *
     * @return string  Description of what the operation is going to do during
     *                 this login.
     */
    public function describe()
    {

        return sprintf(
            _("All completed tasks older than %s days will be permanently deleted."),
            $GLOBALS['prefs']->getValue('purge_completed_keep')
        );
    }

}
