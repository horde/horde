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
        global $conf, $injector, $notification;

        $filter = $injector->getInstance('Horde_Core_Factory_TextFilter');

        /* Scrub HTML. */
        $html = $filter->filter(
            $ui->vars->signature_html,
            'Xss',
            array(
                'charset' => 'UTF-8',
                'return_dom' => true,
                'strip_style_attributes' => false
            )
        );

        if ($img_limit = intval($conf['compose']['htmlsig_img_size'])) {
            $xpath = new DOMXPath($html->dom);
            foreach ($xpath->query('//*[@src]') as $node) {
                $src = $node->getAttribute('src');
                if (Horde_Url_Data::isData($src)) {
                    if (strcasecmp($node->tagName, 'IMG') === 0) {
                        $data_url = new Horde_Url_Data($src);
                        if (($img_limit -= strlen($data_url->data)) < 0) {
                            $notification->push(
                                _("The total size of your HTML signature image data has exceeded the maximum allowed."),
                                'horde.error'
                            );
                            return false;
                        }
                    } else {
                        /* Don't allow any other non-image data URLs. */
                        $node->removeAttribute('src');
                    }
                }
            }
        }

        return $injector->getInstance('IMP_Identity')->setValue(
            'signature_html',
            $html->returnHtml(array('charset' => 'UTF-8'))
        );
    }

}
