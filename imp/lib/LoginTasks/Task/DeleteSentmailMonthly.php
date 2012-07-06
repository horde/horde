<?php
/**
 * Logint tasks module that deletes old sent-mail mailboxes.
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
class IMP_LoginTasks_Task_DeleteSentmailMonthly extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if (($this->active = $GLOBALS['prefs']->getValue('delete_sentmail_monthly_keep')) &&
            $GLOBALS['prefs']->isLocked('delete_sentmail_monthly_keep')) {
            $this->display = Horde_LoginTasks::DISPLAY_NONE;
        }
    }

    /**
     * Purge the old sent-mail mailboxes.
     *
     * @return boolean  Whether any mailboxes were deleted.
     */
    public function execute()
    {
        /* Get list of all mailboxes, parse through and get the list of all
         * old sent-mail mailboxes. Then sort this array according to the
         * date. */
        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
        $sent_mail = $identity->getAllSentmail();

        $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imaptree->setIteratorFilter(IMP_Imap_Tree::FLIST_NOCONTAINER);

        $mbox_list = array();

        foreach ($imaptree as $k => $v) {
            foreach ($sent_mail as $mbox) {
                if (preg_match('/^' . str_replace('/', '\/', $mbox) . '-([^-]+)-([0-9]{4})$/i', $k, $regs)) {
                    $mbox_list[$k] = is_numeric($regs[1])
                        ? mktime(0, 0, 0, $regs[1], 1, $regs[2])
                        : strtotime("$regs[1] 1, $regs[2]");
                }
            }
        }
        arsort($mbox_list, SORT_NUMERIC);

        $return_val = false;

        /* See if any mailboxes need to be purged. */
        $purge = array_slice(array_keys($mbox_list), $GLOBALS['prefs']->getValue('delete_sentmail_monthly_keep'));
        if (count($purge)) {
            $GLOBALS['notification']->push(_("Old sent-mail mailboxes being purged."), 'horde.message');

            /* Delete the old mailboxes now. */
            foreach (IMP_Mailbox::get($purge) as $val) {
                if ($val->delete(array('force' => true))) {
                    $return_val = true;
                }
            }
        }

        return $return_val;
    }

    /**
     * Return information for the login task.
     *
     * @return string  Description of what the operation is going to do during
     *                 this login.
     */
    public function describe()
    {
        return sprintf(_("All old sent-mail mailboxes more than %s months old will be deleted."), $GLOBALS['prefs']->getValue('delete_sentmail_monthly_keep'));
    }

}
