<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'signature_html_select' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        global $conf, $injector, $page_output, $prefs;

        $page_output->addScriptFile('editor.js');
        $page_output->addScriptFile('prefs/signaturehtml.js');
        $page_output->addScriptPackage('IMP_Script_Package_Editor');

        $page_output->addInlineJsVars(array(
            'ImpHtmlSignaturePrefs.sigs' =>
                array(-1 => $prefs->getValue('signature_html')) +
                $injector->getInstance('IMP_Identity')->getAll('signature_html')
        ));

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Text');

        $view->img_limit = $conf['compose']['htmlsig_img_size'];
        $view->signature = $prefs->getValue('signature_html');

        return $view->render('signaturehtml');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        try {
            /* Throws exception if over image size limit. */
            new IMP_Compose_HtmlSignature($ui->vars->signature_html);
        } catch (IMP_Exception $e) {
            $notification->push($e, 'horde.error');
            return false;
        }

        return $injector->getInstance('IMP_Identity')->setValue(
            'signature_html',
            $ui->vars->signature_html
        );
    }

}
