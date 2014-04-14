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
 * Special prefs handling for the 'signature_html_select' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
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

        $page_output->addScriptFile('signaturehtml.js');
        $injector->getInstance('IMP_Editor')->init('signature_html');

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

        $view->img_limit = $conf['compose']['htmlsig_img_size'];
        $view->signature = $prefs->getValue('signature_html');

        return $view->render('signaturehtml');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $notification;

        try {
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
