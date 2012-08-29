<?php
/**
 * Login tasks module that purges old messages in the sent-mail mailbox.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
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
     * Purge old messages in the sent-mail mailbox.
     *
     * @return boolean  Whether any messages were purged from the mailbox.
     */
    public function execute()
    {
        global $injector, $prefs;

        $imp_message = $injector->getInstance('IMP_Message');

        /* Get the current UNIX timestamp minus the number of days specified
         * in 'purge_sentmail_keep'.  If a message has a timestamp prior to
         * this value, it will be deleted. */
        $del_time = new Horde_Date(time() - ($prefs->getValue('purge_sentmail_keep') * 86400));

        foreach ($this->_getMboxes() as $mbox) {
            /* Make sure the sent-mail mailbox exists. */
            if (!$mbox->exists) {
                continue;
            }

            /* Open the sent-mail mailbox and get the list of messages older
             * than 'purge_sentmail_keep' days. */
            $query = new Horde_Imap_Client_Search_Query();
            $query->dateSearch($del_time, Horde_Imap_Client_Search_Query::DATE_BEFORE);
            $msg_ids = $mbox->runSearchQuery($query);

            /* Go through the message list and delete the messages. */
            if ($imp_message->delete($msg_ids, array('nuke' => true))) {
                $msgcount = count($msg_ids);
                if ($msgcount == 1) {
                    $notification->push(sprintf(_("Purging 1 message from sent-mail mailbox %s."), $mbox->display), 'horde.message');
                } else {
                    $notification->push(sprintf(_("Purging %d messages from sent-mail mailbox."), $msgcount, $mbox->display), 'horde.message');
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
        foreach ($this->_getMboxes() as $val) {
            $mbox_list = $val->display_html;
        }

        return sprintf(
            ngettext(
                "All messages in the mailbox \"%s\" older than %s days will be permanently deleted.",
                "All messages in the mailboxes \"%s\" older than %s days will be permanently deleted.",
                count($mbox_list)),
            implode(', ', $mbox_list),
            $GLOBALS['prefs']->getValue('purge_sentmail_keep'));
    }

    /**
     * Returns the list of sent-mail mailboxes.
     *
     * @return array  All sent-mail mailboxes (IMP_Mailbox objects).
     */
    protected function _getMboxes()
    {
        return IMP_Mailbox::get($GLOBALS['injector']->getInstance('IMP_Identity')->getAllSentmail());
    }

}
