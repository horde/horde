<?php
/**
 * Special prefs handling for the 'sentmailselect' preference.
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
class IMP_Prefs_Special_Sentmail extends IMP_Prefs_Special_SpecialMboxes implements Horde_Core_Prefs_Ui_Special
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
        global $injector, $page_output;

        $page_output->addScriptFile('folderprefs.js');

        $identity = $injector->getInstance('IMP_Identity');

        $js = array();
        foreach (array_keys($identity->getAll('id')) as $key) {
            $js[$key] = $identity->getValue('sent_mail_folder', $key)->form_to;
        };

        $page_output->addInlineJsVars(array(
            'ImpFolderPrefs.mboxes' => array('sent_mail' => _("Create a new sent-mail mailbox")),
            'ImpFolderPrefs.sentmail' => $js
        ));

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('default', IMP_Mailbox::formTo(self::PREF_DEFAULT));
        $t->set('label', Horde::label('sent_mail', _("Sent mail mailbox:")));
        $t->set('flist', IMP::flistSelect(array(
            'basename' => true,
            'filter' => array('INBOX'),
            'new_mbox' => true
        )));
        $t->set('special_use', $this->_getSpecialUse(Horde_Imap_Client::SPECIALUSE_SENT));

        return $t->fetch(IMP_TEMPLATES . '/prefs/sentmail.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $prefs;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS) ||
            $prefs->isLocked('sent_mail_folder')) {
            return false;
        }

        if (!$ui->vars->sent_mail && $ui->vars->sent_mail_new) {
            $sent_mail = IMP_Mailbox::get($ui->vars->sent_mail_new)->namespace_append;
        } else {
            $sent_mail = IMP_Mailbox::formFrom($ui->vars->sent_mail);
            if (strpos($sent_mail, self::PREF_SPECIALUSE) === 0) {
                $sent_mail = IMP_Mailbox::get(substr($sent_mail, strlen(self::PREF_SPECIALUSE)));
            } elseif (($sent_mail == self::PREF_DEFAULT) &&
                      ($sm_default = $prefs->getDefault('sent_mail_folder'))) {
                $sent_mail = IMP_Mailbox::get($sm_default)->namespace_append;
            }
        }

        if ($sent_mail && !$sent_mail->create()) {
            return false;
        }

        return $injector->getInstance('IMP_Identity')->setValue('sent_mail_folder', $sent_mail);
    }

}
