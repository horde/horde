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
 * Special prefs handling for the 'smimepublickey' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_SmimePublicKey implements Horde_Core_Prefs_Ui_Special
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
        global $browser, $injector, $page_output, $prefs;

        $page_output->addScriptPackage('IMP_Script_Package_Imp');

        $p_css = new Horde_Themes_Element('prefs.css');
        $page_output->addStylesheet($p_css->fs, $p_css->uri);

        $imp_smime = $injector->getInstance('IMP_Smime');

        /* Get list of Public Keys on keyring. */
        try {
            $pubkey_list = $imp_smime->listPublicKeys();
        } catch (Horde_Exception $e) {
            $pubkey_list = array();
        }

        $smime_url = IMP_Basic_Smime::url();

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Text');

        if (!empty($pubkey_list)) {
            $plist = array();
            $self_url = $ui->selfUrl(array('special' => true, 'token' => true));

            foreach ($pubkey_list as $val) {
                $plist[] = array(
                    'name' => $val['name'],
                    'email' => $val['email'],
                    'view' => Horde::link($smime_url->copy()->add(array('actionID' => 'view_public_key', 'email' => $val['email'])), sprintf(_("View %s Public Key"), $val['name']), null, 'view_key'),
                    'info' => Horde::link($smime_url->copy()->add(array('actionID' => 'info_public_key', 'email' => $val['email'])), sprintf(_("Information on %s Public Key"), $val['name']), null, 'info_key'),
                    'delete' => Horde::link($self_url->copy()->add(array('delete_smime_pubkey' => 1, 'email' => $val['email'])), sprintf(_("Delete %s Public Key"), $val['name']), null, null, "window.confirm('" . addslashes(_("Are you sure you want to delete this public key?")) . "')")
                );
            }
            $view->pubkey_list = $plist;
        }

        if ($browser->allowFileUploads()) {
            $view->can_import = true;
            $view->no_source = !$prefs->getValue('add_source');
            if (!$view->no_source) {
                $page_output->addInlineScript(array(
                    '$("import_smime_public").observe("click", function(e) { ' . Horde::popupJs($smime_url, array('params' => array('actionID' => 'import_public_key', 'reload' => base64_encode($ui->selfUrl()->setRaw(true))), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                ), true);
            }
        }

        return $view->render('smimepublickey');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        if (isset($ui->vars->delete_smime_pubkey)) {
            try {
                $injector->getInstance('IMP_Smime')->deletePublicKey($ui->vars->email);
                $notification->push(sprintf(_("S/MIME Public Key for \"%s\" was successfully deleted."), $ui->vars->email), 'horde.success');
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }

        return false;
    }

}
