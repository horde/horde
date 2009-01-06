<?php
/**
 * Maintenance module that purges old messages in the Trash folder.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Maintenance
 */
class Maintenance_Task_purge_trash extends Maintenance_Task
{
    /**
     * Purge old messages in the Trash folder.
     *
     * @return boolean  Whether any messages were purged from the Trash folder.
     */
    function doMaintenance()
    {
        global $prefs, $notification;

        /* If we aren't using a Trash folder or if there is no Trash
           folder set, just return. */
        $trash_folder = IMP::folderPref($prefs->getValue('trash_folder'), true);
        if (!$prefs->getValue('use_trash') || !$trash_folder) {
            return false;
        }

        /* Make sure the Trash folder exists. */
        $imp_folder = &IMP_Folder::singleton();
        if (!$imp_folder->exists($trash_folder)) {
            return false;
        }

        /* Get the current UNIX timestamp minus the number of days
           specified in 'purge_trash_keep'.  If a message has a
           timestamp prior to this value, it will be deleted. */
        $del_time = new DateTime(time() - ($prefs->getValue('purge_trash_keep') * 86400));
        $month = $del_time->format('n');
        $day = $del_time->format('j');
        $year = $del_time->format('Y');

        /* Get the list of messages older than 'purge_trash_keep' days. */
        $query = new Horde_Imap_Client_Search_Query();
        $query->dateSearch($month, $day, $year, Horde_Imap_Client_Search_Query::DATE_BEFORE);
        $msg_ids = $GLOBALS['imp_search']->runSearchQuery($query, $mbox);
        if (empty($msg_ids)) {
            return false;
        }

        /* Go through the message list and delete the messages. */
        $imp_message = &IMP_Message::singleton();
        if ($imp_message->delete(array($trash_folder => $msg_ids), true)) {
            $msgcount = count($msg_ids);
            if ($msgcount == 1) {
                $notification->push(_("Purging 1 message from Trash folder."), 'horde.message');
            } else {
                $notification->push(sprintf(_("Purging %d messages from Trash folder."), $msgcount), 'horde.message');
            }
        }

        return true;
    }

    /**
     * Return information for the maintenance function.
     *
     * @return string  Description of what the operation is going to do during
     *                 this login.
     */
    function describeMaintenance()
    {
        return sprintf(_("All messages in your \"%s\" folder older than %s days will be permanently deleted."),
                       IMP::displayFolder(IMP::folderPref($GLOBALS['prefs']->getValue('trash_folder'), true)),
                       $GLOBALS['prefs']->getValue('purge_trash_keep'));
    }

}
