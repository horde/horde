<?php
/**
 * Special prefs handling for the 'newmail_soundselect' preference.
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
class IMP_Prefs_Special_NewmailSound implements Horde_Core_Prefs_Ui_Special
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
        global $prefs;

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Tag');
        $view->addHelper('Text');

        $newmail_audio = $view->newmail_audio = $prefs->getValue('newmail_audio');

        $sounds = array();
        foreach (Horde_Themes::soundList() as $key => $val) {
            $sounds[] = array(
                'c' => ($newmail_audio == $key),
                'l' => $key,
                's' => $val->uri,
                'v' => $key
            );
        }
        $view->sounds = $sounds;

        return $view->render('newmailaudio');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        return $GLOBALS['prefs']->setValue('newmail_audio', $ui->vars->newmail_audio);
    }

}
