<?php
/**
 * Special prefs handling for the 'trashselect' preference.
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
class IMP_Prefs_Special_Trash extends IMP_Prefs_Special_SpecialMboxes implements Horde_Core_Prefs_Ui_Special
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
        global $injector, $page_output, $prefs;

        $page_output->addScriptFile('folderprefs.js');
        $page_output->addInlineJsVars(array(
            'ImpFolderPrefs.mboxes.trash' => _("Enter the name for your new trash mailbox.")
        ));

        $imp_search = $injector->getInstance('IMP_Search');
        $trash = IMP_Mailbox::getPref('trash_folder');

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('label', Horde::label('trash', _("Trash mailbox:")));
        $t->set('nombox', IMP_Mailbox::formTo(self::PREF_NO_MBOX));
        $t->set('flist', IMP::flistSelect(array(
            'basename' => true,
            'filter' => array('INBOX'),
            'new_mbox' => true,
            'selected' => $trash
        )));
        $t->set('special_use', $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_TRASH));

        if (!$prefs->isLocked('vfolder') || $imp_search['vtrash']->enabled) {
            $t->set('vtrash', IMP_Mailbox::formTo($imp_search->createSearchId('vtrash')));
            $t->set('vtrash_select', $trash->vtrash);
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/trash.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $prefs;

        $imp_search = $injector->getInstance('IMP_Search');
        $trash = IMP_Mailbox::formFrom($ui->vars->trash);

        if (!$prefs->isLocked('vfolder')) {
            $vtrash = $imp_search['vtrash'];
            $vtrash->enabled = $trash->vtrash;
            $imp_search['vtrash'] = $vtrash;
        }

        if ($this->_updateSpecialMboxes('trash_folder', $trash, $ui->vars->trash_new, Horde_Imap_Client::SPECIALUSE_TRASH, $ui)) {
            $injector->getInstance('IMP_Factory_Imap')->create()->updateFetchIgnore();
            return true;
        }

        return false;
    }

}
