<?php
/**
 * Special prefs handling for the 'encryptselect' preference.
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
class IMP_Prefs_Special_Encrypt implements Horde_Core_Prefs_Ui_Special
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
        $t = $GLOBALS['injector']->createInstance('Horde_Template');

        $t->set('label', Horde::label('default_encrypt', _("Your default encryption method for sending messages:")));
        $t->set('elist', IMP::encryptList());

        return $t->fetch(IMP_TEMPLATES . '/prefs/encrypt.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        return $GLOBALS['prefs']->setValue('default_encrypt', $ui->vars->default_encrypt);
    }

}
