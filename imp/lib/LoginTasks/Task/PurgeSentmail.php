<?php
/**
 * Login tasks module that purges old messages in the sent-mail folder.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_LoginTasks_Task_PurgeSentmail extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($this->interval = $GLOBALS['prefs']->getValue('purge_sentmail_interval')) {
            if ($GLOBALS['prefs']->isLocked('purge_sentmail_interval')) {
                $this->display = Horde_LoginTasks::DISPLAY_NONE;
            }
        } else {
            $this->active = false;
        }
    }

    /**
     * Purge old messages in the sent-mail folder.
     *
     * @return boolean  Whether any messages were purged from the sent-mail
     *                  folder.
     */
    public function execute()
    {
        global $injector, $prefs;

        $imp_message = $injector->getInstance('IMP_Message');
        $imp_search = $injector->getInstance('IMP_Search');

        /* Get the current UNIX timestamp minus the number of days specified
         * in 'purge_sentmail_keep'.  If a message has a timestamp prior to
         * this value, it will be deleted. */
        $del_time = new Horde_Date(time() - ($prefs->getValue('purge_sentmail_keep') * 86400));

        foreach ($this->_getFolders() as $mbox) {
            /* Make sure the sent-mail mailbox exists. */
            if (!$mbox->exists) {
                continue;
            }

            /* Open the sent-mail mailbox and get the list of messages older
             * than 'purge_sentmail_keep' days. */
            $query = new Horde_Imap_Client_Search_Query();
            $query->dateSearch($del_time, Horde_Imap_Client_Search_Query::DATE_BEFORE);
            $msg_ids = $imp_search->runQuery($query, $mbox);

            /* Go through the message list and delete the messages. */
            if ($imp_message->delete($msg_ids, array('nuke' => true))) {
                $msgcount = count($msg_ids);
                if ($msgcount == 1) {
                    $notification->push(sprintf(_("Purging 1 message from sent-mail folder %s."), $mbox->display), 'horde.message');
                } else {
                    $notification->push(sprintf(_("Purging %d messages from sent-mail folder."), $msgcount, $mbox->display), 'horde.message');
                }
            }
        }

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
        $mbox_list = array();
        foreach ($this->_getFolders() as $val) {
            $mbox_list = $val->display;
        }

        return sprintf(_("All messages in the folder(s) \"%s\" older than %s days will be permanently deleted."),
                       implode(', ', $mbox_list),
                       $GLOBALS['prefs']->getValue('purge_sentmail_keep'));
    }

    /**
     * Returns the list of sent-mail folders.
     *
     * @return array  All sent-mail folders (IMP_Mailbox objects).
     */
    protected function _getFolders()
    {
        return array_map(array('IMP_Mailbox', 'get'), $GLOBALS['injector']->getInstance('IMP_Identity')->getAllSentmailfolders());
    }

}
