<?php
/**
 * Logint tasks module that deletes old sent-mail folders.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_LoginTasks_Task_DeleteSentmailMonthly extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->active = $GLOBALS['prefs']->getValue('delete_sentmail_monthly');
        if ($this->active &&
            $GLOBALS['prefs']->isLocked('delete_sentmail_monthly')) {
            $this->display = Horde_LoginTasks::DISPLAY_NONE;
        }
    }

    /**
     * Purge the old sent-mail folders.
     *
     * @return boolean  Whether any sent-mail folders were deleted.
     */
    public function execute()
    {
        /* Get list of all folders, parse through and get the list of all
           old sent-mail folders. Then sort this array according to
           the date. */
        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
        $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');
        $sent_mail_folders = $identity->getAllSentmailFolders();

        $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imaptree->setIteratorFilter(IMP_Imap_Tree::FLIST_NOCONTAINER);

        $folder_array = array();

        foreach ($imaptree as $k => $v) {
            foreach ($sent_mail_folders as $folder) {
                if (preg_match('/^' . str_replace('/', '\/', $folder) . '-([^-]+)-([0-9]{4})$/i', $k, $regs)) {
                    $folder_array[$k] = Horde_String::convertCharset((is_numeric($regs[1])) ? mktime(0, 0, 0, $regs[1], 1, $regs[2]) : strtotime("$regs[1] 1, $regs[2]"), 'UTF-8', 'UTF7-IMAP');
                }
            }
        }
        arsort($folder_array, SORT_NUMERIC);

        /* See if any folders need to be purged. */
        $purge_folders = array_slice(array_keys($folder_array), $GLOBALS['prefs']->getValue('delete_sentmail_monthly_keep'));
        if (count($purge_folders)) {
            $GLOBALS['notification']->push(_("Old sent-mail folders being purged."), 'horde.message');

            /* Delete the old folders now. */
            if ($imp_folder->delete($purge_folders, true)) {
                return true;
            }
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
        return sprintf(_("All old sent-mail folders more than %s months old will be deleted."), $GLOBALS['prefs']->getValue('delete_sentmail_monthly_keep'));
    }

}
