<?php
/**
 * Special prefs handling for the 'signature_html_select' preference.
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
class IMP_Prefs_Special_HtmlSignature implements Horde_Core_Prefs_Ui_Special
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

        $page_output->addScriptFile('signaturehtml.js');
        IMP_Ui_Editor::init(false, 'signature_html');

        $identity = $injector->getInstance('IMP_Identity');

        $js = array(-1 => $prefs->getValue('signature_html'));
        foreach (array_keys($identity->getAll('id')) as $key) {
            $js[$key] = $identity->getValue('signature_html', $key);
        };

        $page_output->addInlineJsVars(array(
            'ImpHtmlSignaturePrefs.sigs' => $js
        ));

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Text');

        $view->signature = $prefs->getValue('signature_html');

        return $view->render('signaturehtml');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        return $GLOBALS['injector']->getInstance('IMP_Identity')->setValue('signature_html', $ui->vars->signature_html);
    }

}
