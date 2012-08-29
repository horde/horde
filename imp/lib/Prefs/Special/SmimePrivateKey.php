<?php
/**
 * Special prefs handling for the 'smimeprivatekey' preference.
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
class IMP_Prefs_Special_SmimePrivateKey implements Horde_Core_Prefs_Ui_Special
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

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('personalkey-help', Horde_Help::link('imp', 'smime-overview-personalkey'));

        if (!Horde::isConnectionSecure()) {
            $t->set('notsecure', true);
        } else {
            $smime_url = Horde::url('smime.php');

            $t->set('has_key', $prefs->getValue('smime_public_key') && $prefs->getValue('smime_private_key'));
            if ($t->get('has_key')) {
                $t->set('viewpublic', Horde::link($smime_url->copy()->add('actionID', 'view_personal_public_key'), _("View Personal Public Certificate"), null, 'view_key'));
                $t->set('infopublic', Horde::link($smime_url->copy()->add('actionID', 'info_personal_public_key'), _("Information on Personal Public Certificate"), null, 'info_key'));

                if ($passphrase = $injector->getInstance('IMP_Crypt_Smime')->getPassphrase()) {
                    $t->set('passphrase', Horde::link($ui->selfUrl(array('special' => true, 'token' => true))->add('unset_smime_passphrase', 1), _("Unload Passphrase")) . _("Unload Passphrase"));
                } else {
                    $imple = $injector->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_PassphraseDialog', array(
                        'params' => array(
                            'reload' => $ui->selfUrl()->setRaw(true)
                        ),
                        'type' => 'smimePersonal'
                    ));
                    $t->set('passphrase', Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getDomId())) . _("Enter Passphrase"));
                }

                $t->set('viewprivate', Horde::link($smime_url->copy()->add('actionID', 'view_personal_private_key'), _("View Personal Private Key"), null, 'view_key'));
                $t->set('personalkey-delete-help', Horde_Help::link('imp', 'smime-delete-personal-certs'));
                $page_output->addInlineScript(array(
                    '$("delete_smime_personal").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Are you sure you want to delete your keypair? (This is NOT recommended!)"), Horde_Serialize::JSON, 'UTF-8') . ')) { e.stop(); } })'
                ), true);
            } elseif ($session->get('imp', 'file_upload')) {
                $t->set('import-cert-help', Horde_Help::link('imp', 'smime-import-personal-certs'));

                $page_output->addInlineScript(array(
                    '$("import_smime_personal").observe("click", function(e) { ' . Horde::popupJs($smime_url, array('params' => array('actionID' => 'import_personal_certs', 'reload' => $session->store($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                ), true);
            }
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/smimeprivatekey.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        if (isset($ui->vars->delete_smime_personal)) {
            $injector->getInstance('IMP_Crypt_Smime')->deletePersonalKeys();
            $notification->push(_("Personal S/MIME keys deleted successfully."), 'horde.success');
        } elseif (isset($ui->vars->unset_smime_passphrase)) {
            $injector->getInstance('IMP_Crypt_Smime')->unsetPassphrase();
            $notification->push(_("S/MIME passphrase successfully unloaded."), 'horde.success');
        }

        return false;
    }

}
