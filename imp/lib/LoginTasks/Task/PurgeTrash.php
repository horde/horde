<?php
/**
 * Login tasks module that purges old messages in the Trash folder.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_LoginTasks_Task_PurgeTrash extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->active = $GLOBALS['prefs']->getValue('purge_trash');
        if ($this->active) {
            $this->interval = $GLOBALS['prefs']->getValue('purge_trash_interval');
            if ($GLOBALS['prefs']->isLocked('purge_trash')) {
                $this->display = Horde_LoginTasks::DISPLAY_NONE;
            }
        }
    }

    /**
     * Purge old messages in the Trash folder.
     *
     * @return boolean  Whether any messages were purged from the Trash folder.
     */
    public function execute()
    {
        /* If we aren't using a Trash folder or if there is no Trash
           folder set, just return. */
        $trash_folder = IMP::folderPref($GLOBALS['prefs']->getValue('trash_folder'), true);
        if (!$GLOBALS['prefs']->getValue('use_trash') || !$trash_folder) {
            return false;
        }

        /* Make sure the Trash folder exists. */
        if (!$GLOBALS['injector']->getInstance('IMP_Folder')->exists($trash_folder)) {
            return false;
        }

        /* Get the current UNIX timestamp minus the number of days
           specified in 'purge_trash_keep'.  If a message has a
           timestamp prior to this value, it will be deleted. */
        $del_time = new Horde_Date(time() - ($GLOBALS['prefs']->getValue('purge_trash_keep') * 86400));

        /* Get the list of messages older than 'purge_trash_keep' days. */
        $query = new Horde_Imap_Client_Search_Query();
        $query->dateSearch($del_time, Horde_Imap_Client_Search_Query::DATE_BEFORE);
        $msg_ids = $GLOBALS['injector']->getInstance('IMP_Search')->runSearchQuery($query, $trash_folder);

        /* Go through the message list and delete the messages. */
        if ($GLOBALS['injector']->getInstance('IMP_Message')->delete($msg_ids, array('nuke' => true))) {
            $msgcount = count($msg_ids);
            $GLOBALS['notification']->push(sprintf(ngettext("Purging %d message from Trash folder.", "Purging %d messages from Trash folder.", $msgcount), $msgcount), 'horde.message');
            return true;
        }

        return false;
    }

    /**
     * Return information for the login task.
     *
     * @return string  Description of what the operation is going to do during
     *                 this login.
     */
    public function describe()
    {
        return sprintf(_("All messages in your \"%s\" folder older than %s days will be permanently deleted."),
                       IMP::displayFolder(IMP::folderPref($GLOBALS['prefs']->getValue('trash_folder'), true)),
                       $GLOBALS['prefs']->getValue('purge_trash_keep'));
    }

}
