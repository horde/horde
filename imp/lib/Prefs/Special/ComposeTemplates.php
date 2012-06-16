<?php
/**
 * Special prefs handling for the 'composetemplates_management' preference.
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
class IMP_Prefs_Special_ComposeTemplates extends IMP_Prefs_Special_SpecialMboxes implements Horde_Core_Prefs_Ui_Special
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

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if (!$prefs->isLocked('composetemplates_mbox')) {
            $page_output->addScriptFile('folderprefs.js');
            $page_output->addInlineJsVars(array(
                'ImpFolderPrefs.mboxes.templates' => _("Enter the name for your new compose templates mailbox.")
            ));

            $t->set('mbox_label', Horde::label('templates', _("Compose Templates mailbox:")));
            $t->set('mbox_nomailbox', IMP_Mailbox::formTo(self::PREF_NO_MBOX));
            $t->set('mbox_flist', IMP::flistSelect(array(
                'basename' => true,
                'filter' => array('INBOX'),
                'new_mbox' => true,
                'selected' => IMP_Mailbox::getPref('composetemplates_mbox')
            )));
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/composetemplates.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        if ($GLOBALS['prefs']->isLocked('composetemplates_mbox')) {
            return false;
        }

        return $this->_updateSpecialMboxes(
            'composetemplates_mbox',
            IMP_Mailbox::formFrom($ui->vars->templates),
            $ui->vars->templates_new,
            null,
            $ui
        );
    }

}
