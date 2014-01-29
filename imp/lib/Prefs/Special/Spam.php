<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'spamselect' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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

        $iterator = new IMP_Ftree_IteratorFilter_Mailboxes(
            IMP_Ftree_IteratorFilter::create(IMP_Ftree_IteratorFilter::NO_NONIMAP)
        );
        $iterator->mboxes = array('INBOX');

        $view->flist = new IMP_Ftree_Select(array(
            'basename' => true,
            'iterator' => $iterator,
            'new_mbox' => true,
            'selected' => IMP_Mailbox::getPref(IMP_Mailbox::MBOX_SPAM)
        ));
        $view->special_use = $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_JUNK);

        return $view->render('spam');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector;

        if (!$this->_updateSpecialMboxes(IMP_Mailbox::MBOX_SPAM, IMP_Mailbox::formFrom($ui->vars->spam), $ui->vars->spam_new, Horde_Imap_Client::SPECIALUSE_JUNK, $ui)) {
            return false;
        }

        $injector->getInstance('IMP_Factory_Imap')->create()->updateFetchIgnore();
        return true;
    }

}
