<?php
/**
 * Login tasks module that renames the sent-mail folder.
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
     * Renames the old sent-mail folders.
     *
     * @return boolean  Whether all sent-mail folders were renamed.
     */
    public function execute()
    {
        $success = true;

        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
        $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');

        foreach ($identity->getAllSentmailfolders() as $sent_folder) {
            /* Display a message to the user and rename the folder.
               Only do this if sent-mail folder currently exists. */
            if ($imp_folder->exists($sent_folder)) {
                $old_folder = $this->_renameSentmailMonthlyName($sent_folder);
                $GLOBALS['notification']->push(sprintf(_("%s folder being renamed at the start of the month."), IMP::displayFolder($sent_folder)), 'horde.message');
                if ($imp_folder->exists($old_folder)) {
                    $GLOBALS['notification']->push(sprintf(_("%s already exists. Your %s folder was not renamed."), IMP::displayFolder($old_folder), IMP::displayFolder($sent_folder)), 'horde.warning');
                    $success = false;
                } else {
                    $success =
                        $imp_folder->rename($sent_folder, $old_folder, true) &&
                        $imp_folder->create($sent_folder, $GLOBALS['prefs']->getValue('subscribe'));
                }
            }
        }

        return $success;
    }

    /**
     * Returns information for the login task.
     *
     * @return string  Description of what the operation is going to do during
     *                 this login.
     */
    public function describe()
    {
        $identity = $GLOBALS['injector']->getInstance('IMP_Identity');

        $new_folders = $old_folders = array();
        foreach ($identity->getAllSentmailfolders() as $folder) {
            $old_folders[] = IMP::displayFolder($folder);
            $new_folders[] = IMP::displayFolder($this->_renameSentmailMonthlyName($folder));
        }

        return sprintf(_("The current folder(s) \"%s\" will be renamed to \"%s\"."), implode(', ', $old_folders), implode(', ', $new_folders));
    }

    /**
     * Determines the name the sent-mail folder will be renamed to.
     * <pre>
     * Folder name: sent-mail-month-year
     *   month = English:         3 letter abbreviation
     *           Other Languages: Month value (01-12)
     *   year  = 4 digit year
     * The folder name needs to be in this specific format (as opposed to a
     *   user-defined one) to ensure that 'delete_sentmail_monthly' processing
     *   can accurately find all the old sent-mail folders.
     * </pre>
     *
     * @param string $folder  The name of the sent-mail folder to rename.
     *
     * @return string  New sent-mail folder name.
     */
    protected function _renameSentmailMonthlyName($folder)
    {
        // @TODO
        $last_maintenance = $GLOBALS['prefs']->getValue('last_maintenance');
        $last_maintenance = empty($last_maintenance) ? mktime(0, 0, 0, date('m') - 1, 1) : $last_maintenance;

        $text = (substr($GLOBALS['language'], 0, 2) == 'en') ? strtolower(strftime('-%b-%Y', $last_maintenance)) : strftime('-%m-%Y', $last_maintenance);

        return $folder . Horde_String::convertCharset($text, $GLOBALS['registry']->getExternalCharset(), 'UTF7-IMAP');
    }

}
