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
 * Special prefs handling for the 'composetemplates_management' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_ComposeTemplates
extends IMP_Prefs_Special_SpecialMboxes
implements Horde_Core_Prefs_Ui_Special
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

        if ($prefs->isLocked(IMP_Mailbox::MBOX_TEMPLATES)) {
            return '';
        }

        $page_output->addScriptFile('folderprefs.js');
        $page_output->addInlineJsVars(array(
            'ImpFolderPrefs.mboxes.templates' => _("Enter the name for your new compose templates mailbox.")
        ));

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Horde_Core_View_Helper_Label');

        $iterator = new IMP_Ftree_IteratorFilter(
            $injector->getInstance('IMP_Ftree')
        );
        $iterator->add($iterator::NONIMAP);
        $iterator->mboxes = array('INBOX');

        $view->mbox_flist = new IMP_Ftree_Select(array(
            'basename' => true,
            'iterator' => $iterator,
            'new_mbox' => true,
            'selected' => IMP_Mailbox::getPref(IMP_Mailbox::MBOX_TEMPLATES)
        ));
        $view->mbox_nomailbox = IMP_Mailbox::formTo(self::PREF_NO_MBOX);

        return $view->render('composetemplates');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        if ($GLOBALS['prefs']->isLocked(IMP_Mailbox::MBOX_TEMPLATES)) {
            return false;
        }

        return $this->_updateSpecialMboxes(
            IMP_Mailbox::MBOX_TEMPLATES,
            IMP_Mailbox::formFrom($ui->vars->templates),
            $ui->vars->templates_new,
            null,
            $ui
        );
    }

}
