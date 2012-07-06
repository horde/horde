<?php
/**
 * Special prefs handling for the 'draftsselect' preference.
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
class IMP_Prefs_Special_Drafts extends IMP_Prefs_Special_SpecialMboxes implements Horde_Core_Prefs_Ui_Special
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
        global $injector, $notification, $page_output, $registry, $session;

        $page_output->addScriptFile('folderprefs.js');
        $page_output->addInlineJsVars(array(
            'ImpFolderPrefs.mboxes.drafts' => _("Enter the name for your new drafts mailbox.")
        ));

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('label', Horde::label('drafts', _("Drafts mailbox:")));
        $t->set('nombox', IMP_Mailbox::formTo(self::PREF_NO_MBOX));
        $t->set('flist', IMP::flistSelect(array(
            'basename' => true,
            'filter' => array('INBOX'),
            'new_mbox' => true,
            'selected' => IMP_Mailbox::getPref('drafts_folder')
        )));
        $t->set('special_use', $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_DRAFTS));

        return $t->fetch(IMP_TEMPLATES . '/prefs/drafts.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        return $this->_updateSpecialMboxes(
            'drafts_folder',
            IMP_Mailbox::formFrom($ui->vars->drafts),
            $ui->vars->drafts_new,
            Horde_Imap_Client::SPECIALUSE_DRAFTS,
            $ui
        );
    }

}
