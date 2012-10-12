<?php
/**
 * Special prefs handling for the 'spamselect' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Prefs_Special_Spam extends IMP_Prefs_Special_SpecialMboxes implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $page_output;

        $page_output->addScriptFile('folderprefs.js');
        $page_output->addInlineJsVars(array(
            'ImpFolderPrefs.mboxes.spam' => _("Enter the name for your new spam mailbox.")
        ));

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Horde_Core_View_Helper_Label');

        $view->nombox = IMP_Mailbox::formTo(self::PREF_NO_MBOX);
        $view->flist = IMP::flistSelect(array(
            'basename' => true,
            'filter' => array('INBOX'),
            'new_mbox' => true,
            'selected' => IMP_Mailbox::getPref('spam_folder')
        ));
        $view->special_use = $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_JUNK);

        return $view->render('spam');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector;

        if (!$this->_updateSpecialMboxes('spam_folder', IMP_Mailbox::formFrom($ui->vars->spam), $ui->vars->spam_new, Horde_Imap_Client::SPECIALUSE_JUNK, $ui)) {
            return false;
        }

        $injector->getInstance('IMP_Factory_Imap')->create()->updateFetchIgnore();
        return true;
    }

}
