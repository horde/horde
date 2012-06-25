<?php
/**
 * Special prefs handling for the 'smimepublickey' preference.
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
        global $injector, $page_output, $prefs, $session;

        $page_output->addScriptFile('imp.js');

        $imp_smime = $injector->getInstance('IMP_Crypt_Smime');

        /* Get list of Public Keys on keyring. */
        try {
            $pubkey_list = $imp_smime->listPublicKeys();
        } catch (Horde_Exception $e) {
            $pubkey_list = array();
        }

        $smime_url = Horde::url('smime.php');

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('manage_pubkey-help', Horde_Help::link('imp', 'smime-manage-pubkey'));

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
            $t->set('pubkey_list', $plist);
        }

        if ($session->get('imp', 'file_upload')) {
            $t->set('can_import', true);
            $t->set('no_source', !$prefs->getValue('add_source'));
            if (!$t->get('no_source')) {
                $t->set('import_pubkey-help', Horde_Help::link('imp', 'smime-import-pubkey'));

                $page_output->addInlineScript(array(
                    '$("import_smime_public").observe("click", function(e) { ' . Horde::popupJs($smime_url, array('params' => array('actionID' => 'import_public_key', 'reload' => $session->store($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                ), true);
            }
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/smimepublickey.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        if (isset($ui->vars->delete_smime_pubkey)) {
            try {
                $injector->getInstance('IMP_Crypt_Smime')->deletePublicKey($ui->vars->email);
                $notification->push(sprintf(_("S/MIME Public Key for \"%s\" was successfully deleted."), $ui->vars->email), 'horde.success');
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }

        return false;
    }

}
