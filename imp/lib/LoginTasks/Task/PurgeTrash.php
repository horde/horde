<?php
/**
 * Login tasks module that purges old messages in the Trash mailbox.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_LoginTasks_Task_PurgeTrash extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($this->interval = $GLOBALS['prefs']->getValue('purge_trash_interval')) {
            if ($GLOBALS['prefs']->isLocked('purge_trash_interval')) {
                $this->display = Horde_LoginTasks::DISPLAY_NONE;
            }
        } else {
            $this->active = false;
        }
    }

    /**
     * Purge old messages in the Trash mailbox.
     *
     * @return boolean  Whether any messages were purged from the mailbox.
     */
    public function execute()
    {
        global $injector, $notification, $prefs;

        if (!$prefs->getValue('use_trash') ||
            !($trash = IMP_Mailbox::getPref('trash_folder')) ||
            $trash->vtrash ||
            !$trash->exists) {
            return false;
        }

        /* Get the current UNIX timestamp minus the number of days
           specified in 'purge_trash_keep'.  If a message has a
           timestamp prior to this value, it will be deleted. */
        $del_time = new Horde_Date(time() - ($prefs->getValue('purge_trash_keep') * 86400));

        /* Get the list of messages older than 'purge_trash_keep' days. */
        $query = new Horde_Imap_Client_Search_Query();
        $query->dateSearch($del_time, Horde_Imap_Client_Search_Query::DATE_BEFORE);
        $msg_ids = $trash->runSearchQuery($query);

        /* Go through the message list and delete the messages. */
        if (!$injector->getInstance('IMP_Message')->delete($msg_ids, array('nuke' => true))) {
            return false;
        }

        $msgcount = count($msg_ids);
        $notification->push(sprintf(ngettext("Purging %d message from Trash mailbox.", "Purging %d messages from Trash mailbox.", $msgcount), $msgcount), 'horde.message');
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
        return sprintf(_("All messages in your \"%s\" mailbox older than %s days will be permanently deleted."),
                       IMP_Mailbox::getPref('trash_folder')->display_html,
                       $GLOBALS['prefs']->getValue('purge_trash_keep'));
    }

}
