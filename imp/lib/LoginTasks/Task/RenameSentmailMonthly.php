<?php
/**
 * Login tasks module that renames sent-mail mailboxes every month.
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
class IMP_LoginTasks_Task_RenameSentmailMonthly extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->active = $GLOBALS['prefs']->getValue('rename_sentmail_monthly');
        if ($this->active &&
            $GLOBALS['prefs']->isLocked('rename_sentmail_monthly')) {
            $this->display = Horde_LoginTasks::DISPLAY_NONE;
        }
    }

    /**
     * Renames the old sent-mail mailboxes.
     *
     * Mailbox name: sent-mail-month-year
     *   month = English:         3 letter abbreviation
     *           Other Languages: Month value (01-12)
     *   year  = 4 digit year
     *
     * The mailbox name needs to be in this specific format (as opposed to a
     * user-defined one) to ensure that 'delete_sentmail_monthly' processing
     * can accurately find all the old sent-mail mailboxes.
     *
     * @return boolean  Whether all sent-mail mailboxes were renamed.
     */
    public function execute()
    {
        global $injector, $notification;

        $date_format = (substr($GLOBALS['language'], 0, 2) == 'en')
            ? 'M-Y'
            : 'm-Y';

        $datetime = new DateTime();
        $now = $datetime->format($date_format);

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        foreach ($this->_getSentmail() as $sent) {
            /* Display a message to the user and rename the mailbox.
             * Only do this if sent-mail mailbox currently exists. */
            if ($sent->exists) {
                $notification->push(sprintf(_("%s mailbox being renamed at the start of the month."), $sent->display), 'horde.message');

                $query = new Horde_Imap_Client_Fetch_Query();
                $query->imapDate();
                $query->uid();

                $res = $imp_imap->fetch($sent, $query);

                $msgs = array();
                foreach ($res as $val) {
                    $date_string = $val->getImapDate()->format($date_format);
                    if (!isset($msgs[$date_string])) {
                        $msgs[$date_string] = $imp_imap->getIdsOb();
                    }
                    $msgs[$date_string]->add($val->getUid());
                }

                unset($msgs[$now]);
                foreach ($msgs as $key => $val) {
                    $new_mbox = IMP_Mailbox::get(strval($sent) . '-' . Horde_String::lower($key));

                    $imp_imap->copy($sent, $new_mbox, array(
                        'create' => true,
                        'ids' => $val,
                        'move' => true
                    ));
                }
            }
        }

        return true;
    }

    /**
     * Returns information for the login task.
     *
     * @return string  Description of what the operation is going to do during
     *                 this login.
     */
    public function describe()
    {
        $mbox_list = array();

        foreach ($this->_getSentmail() as $mbox) {
            $mbox_list[] = $mbox->display_html;
        }

        return sprintf(_("The current sent-mail mailbox(es) \"%s\" will be renamed."), implode(', ', $mbox_list));
    }

    /**
     * Returns the list of sent-mail mailboxes.
     *
     * @return array  All sent-mail mailboxes (IMP_Mailbox objects).
     */
    protected function _getSentmail()
    {
        return IMP_Mailbox::get($GLOBALS['injector']->getInstance('IMP_Identity')->getAllSentmail());
    }

}
