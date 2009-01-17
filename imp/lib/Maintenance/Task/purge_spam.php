<?php
/**
 * Maintenance module that purges old messages in the Spam folder.  Based on
 * the purge_trash task, written by Michael Slusarz <slusarz@horde.org>.
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Matt Selsky <selsky@columbia.edu>
 * @package Horde_Maintenance
 */
class Maintenance_Task_purge_spam extends Maintenance_Task
{
    /**
     * Purge old messages in the Spam folder.
     *
     * @return boolean  Whether any messages were purged from the Spam folder.
     */
    function doMaintenance()
    {
        global $prefs, $notification;

        /* If there is no Spam folder set, just return. */
        $spam_folder = IMP::folderPref($prefs->getValue('spam_folder'), true);
        if (!$spam_folder) {
            return false;
        }

        /* Make sure the Spam folder exists. */
        $imp_folder = IMP_Folder::singleton();
        if (!$imp_folder->exists($spam_folder)) {
            return false;
        }

        /* Get the current UNIX timestamp minus the number of days
           specified in 'purge_spam_keep'.  If a message has a
           timestamp prior to this value, it will be deleted. */
        $del_time = new Horde_Date(time() - ($prefs->getValue('purge_spam_keep') * 86400));
        $month = $del_time->format('n');
        $day = $del_time->format('j');
        $year = $del_time->format('Y');

        /* Get the list of messages older than 'purge_spam_keep' days. */
        $query = new Horde_Imap_Client_Search_Query();
        $query->dateSearch($month, $day, $year, Horde_Imap_Client_Search_Query::DATE_BEFORE);
        $msg_ids = $GLOBALS['imp_search']->runSearchQuery($query, $mbox);
        if (empty($msg_ids)) {
            return false;
        }

        /* Go through the message list and delete the messages. */
        $imp_message = IMP_Message::singleton();
        if ($imp_message->delete(array($spam_folder => $msg_ids), true)) {
            $msgcount = count($msg_ids);
            if ($msgcount == 1) {
                $notification->push(_("Purging 1 message from Spam folder."), 'horde.message');
            } else {
                $notification->push(sprintf(_("Purging %d messages from Spam folder."), $msgcount), 'horde.message');
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
                       IMP::displayFolder(IMP::folderPref($GLOBALS['prefs']->getValue('spam_folder'), true)),
                       $GLOBALS['prefs']->getValue('purge_spam_keep'));
    }

}
