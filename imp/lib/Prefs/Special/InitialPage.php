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
 * Special prefs handling for the 'initialpageselect' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_InitialPage implements Horde_Core_Prefs_Ui_Special
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
        global $injector, $prefs;

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('Tag');

        $iterator = new IMP_Ftree_IteratorFilter(
            $injector->getInstance('IMP_Ftree')
        );
        $iterator->add($iterator::REMOTE);

        if (!($initial_page = $prefs->getValue('initial_page'))) {
            $initial_page = 'INBOX';
        }
        $view->folder_page = IMP_Mailbox::formTo(IMP::INITIAL_FOLDERS);
        $view->folder_sel = ($initial_page == IMP::INITIAL_FOLDERS);
        $view->flist = new IMP_Ftree_Select(array(
            'basename' => true,
            'inc_vfolder' => true,
            'iterator' => $iterator,
            'selected' => $initial_page
        ));

        return $view->render('initialpage');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        return $GLOBALS['prefs']->setValue('initial_page', strval(IMP_Mailbox::formFrom($ui->vars->initial_page)));
    }

}
