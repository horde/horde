<?php
/**
 * Special prefs handling for the 'pgpprivatekey' preference.
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
class IMP_Prefs_Special_PgpPrivateKey implements Horde_Core_Prefs_Ui_Special
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
        $page_output->addScriptFile('pgp.js');
        Horde_Core_Ui_JsCalendar::init();
        $page_output->addInlineJsVars(array(
            'ImpPgp.months' => Horde_Core_Ui_JsCalendar::months()
        ));

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('personalkey-help', Horde_Help::link('imp', 'pgp-overview-personalkey'));

        if (!Horde::isConnectionSecure()) {
            $t->set('notsecure', true);
        } else {
            $pgp_url = Horde::url('pgp.php');

            $t->set('has_key', $prefs->getValue('pgp_public_key') && $prefs->getValue('pgp_private_key'));
            if ($t->get('has_key')) {
                $t->set('viewpublic', Horde::link($pgp_url->copy()->add('actionID', 'view_personal_public_key'), _("View Personal Public Key"), null, 'view_key'));
                $t->set('infopublic', Horde::link($pgp_url->copy()->add('actionID', 'info_personal_public_key'), _("Information on Personal Public Key"), null, 'info_key'));
                $t->set('sendkey', Horde::link($ui->selfUrl(array('special' => true, 'token' => true))->add('send_pgp_key', 1), _("Send Key to Public Keyserver")));
                $t->set('personalkey-public-help', Horde_Help::link('imp', 'pgp-personalkey-public'));

                if ($passphrase = $injector->getInstance('IMP_Crypt_Pgp')->getPassphrase('personal')) {
                    $t->set('passphrase', Horde::link($ui->selfUrl(array('special' => true, 'token' => true))->add('unset_pgp_passphrase', 1), _("Unload Passphrase")) . _("Unload Passphrase"));
                } else {
                    $imple = $injector->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_PassphraseDialog', array(
                        'params' => array(
                            'reload' => $ui->selfUrl()->setRaw(true)
                        ),
                        'type' => 'pgpPersonal'
                    ));
                    $t->set('passphrase', Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getDomId())) . _("Enter Passphrase"));
                }

                $t->set('viewprivate', Horde::link($pgp_url->copy()->add('actionID', 'view_personal_private_key'), _("View Personal Private Key"), null, 'view_key'));
                $t->set('infoprivate', Horde::link($pgp_url->copy()->add('actionID', 'info_personal_private_key'), _("Information on Personal Private Key"), null, 'info_key'));
                $t->set('personalkey-private-help', Horde_Help::link('imp', 'pgp-personalkey-private'));
                $t->set('personalkey-delete-help', Horde_Help::link('imp', 'pgp-personalkey-delete'));
                $page_output->addInlineScript(array(
                    '$("delete_pgp_privkey").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Are you sure you want to delete your keypair? (This is NOT recommended!)"), Horde_Serialize::JSON, 'UTF-8') . ')) { e.stop(); } })'
                ), true);
            } else {
                $imp_identity = $injector->getInstance('IMP_Identity');
                $t->set('fullname', $imp_identity->getFullname());
                $t->set('personalkey-create-name-help', Horde_Help::link('imp', 'pgp-personalkey-create-name'));
                $t->set('personalkey-create-comment-help', Horde_Help::link('imp', 'pgp-personalkey-create-comment'));
                $t->set('fromaddr', strval($imp_identity->getFromAddress()));
                $t->set('personalkey-create-email-help', Horde_Help::link('imp', 'pgp-personalkey-create-email'));
                $t->set('personalkey-create-keylength-help', Horde_Help::link('imp', 'pgp-personalkey-create-keylength'));
                $t->set('personalkey-create-passphrase-help', Horde_Help::link('imp', 'pgp-personalkey-create-passphrase'));

                $page_output->addInlineScript(array(
                    '$("create_pgp_key").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Key generation may take a long time to complete.  Continue with key generation?"), Horde_Serialize::JSON, 'UTF-8') . ')) { e.stop(); } })'
                ), true);

                if ($session->get('imp', 'file_upload')) {
                    $t->set('import_pgp_private', true);
                    $page_output->addInlineScript(array(
                        '$("import_pgp_personal").observe("click", function(e) { ' . Horde::popupJs($pgp_url, array('params' => array('actionID' => 'import_personal_key', 'reload' => $session->store($ui->selfUrl()->setRaw(true), false)), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                    ), true);
                }

                $t->set('personalkey-create-actions-help', Horde_Help::link('imp', 'pgp-personalkey-create-actions'));
            }
        }

        return $t->fetch(IMP_TEMPLATES . '/prefs/pgpprivatekey.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification;

        if (isset($ui->vars->delete_pgp_privkey)) {
            $injector->getInstance('IMP_Crypt_Pgp')->deletePersonalKeys();
            $notification->push(_("Personal PGP keys deleted successfully."), 'horde.success');
        } elseif (isset($ui->vars->create_pgp_key)) {
            /* Check that fields are filled out (except for Comment) and that
             * the passphrases match. */
            if (empty($ui->vars->generate_realname) ||
                empty($ui->vars->generate_email)) {
                $notification->push(_("Name and/or email cannot be empty"), 'horde.error');
            } elseif (empty($ui->vars->generate_passphrase1) ||
                      empty($ui->vars->generate_passphrase2)) {
                $notification->push(_("Passphrases cannot be empty"), 'horde.error');
            } elseif ($ui->vars->generate_passphrase1 !== $ui->vars->generate_passphrase2) {
               $notification->push(_("Passphrases do not match"), 'horde.error');
            } else {
                /* Expire date is delivered in UNIX timestamp in
                 * milliseconds, not seconds. */
                $expire_date = $ui->vars->generate_expire
                    ? null
                    : ($ui->vars->generate_expire_date / 1000);
                try {
                    $injector->getInstance('IMP_Crypt_Pgp')->generatePersonalKeys($ui->vars->generate_realname, $ui->vars->generate_email, $ui->vars->generate_passphrase1, $ui->vars->_generate_comment, $ui->vars->generate_keylength, $expire_date);
                    $notification->push(_("Personal PGP keypair generated successfully."), 'horde.success');
                } catch (Exception $e) {
                    $notification->push($e);
                }
            }
        } elseif (isset($ui->vars->send_pgp_key)) {
            try {
                $imp_pgp = $injector->getInstance('IMP_Crypt_Pgp');
                $imp_pgp->sendToPublicKeyserver($imp_pgp->getPersonalPublicKey());
                $notification->push(_("Key successfully sent to the public keyserver."), 'horde.success');
            } catch (Exception $e) {
                $notification->push($e);
            }
        } elseif (isset($ui->vars->unset_pgp_passphrase)) {
            $injector->getInstance('IMP_Crypt_Pgp')->unsetPassphrase('personal');
            $notification->push(_("PGP passphrase successfully unloaded."), 'horde.success');
        }

        return false;
    }

}
