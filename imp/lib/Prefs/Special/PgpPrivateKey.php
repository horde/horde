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
 * Special prefs handling for the 'pgpprivatekey' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        global $browser, $conf, $injector, $page_output, $prefs;

        $page_output->addScriptPackage('IMP_Script_Package_Imp');

        $p_css = new Horde_Themes_Element('prefs.css');
        $page_output->addStylesheet($p_css->fs, $p_css->uri);

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');

        if (!Horde::isConnectionSecure()) {
            $view->notsecure = true;
        } else {
            $pgp_url = IMP_Basic_Pgp::url();

            $view->has_key = ($prefs->getValue('pgp_public_key') && $prefs->getValue('pgp_private_key'));
            if ($view->has_key) {
                $view->viewpublic = Horde::link($pgp_url->copy()->add('actionID', 'view_personal_public_key'), _("View Personal Public Key"), null, 'view_key');
                $view->infopublic = Horde::link($pgp_url->copy()->add('actionID', 'info_personal_public_key'), _("Information on Personal Public Key"), null, 'info_key');
                $view->sendkey = Horde::link($ui->selfUrl(array(
                    'special' => true,
                    'token' => true
                ))->add('send_pgp_key', 1), _("Send Key to Public Keyserver"));

                if ($injector->getInstance('IMP_Pgp')->getPassphrase('personal')) {
                    $view->passphrase = Horde::link($ui->selfUrl(array(
                        'special' => true,
                        'token' => true
                    ))->add('unset_pgp_passphrase', 1), _("Unload Passphrase")) . _("Unload Passphrase");
                } else {
                    $imple = $injector->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_PassphraseDialog', array(
                        'params' => array(
                            'reload' => $ui->selfUrl()->setRaw(true)
                        ),
                        'type' => 'pgpPersonal'
                    ));
                    $view->passphrase = Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getDomId())) . _("Enter Passphrase");
                }

                $view->viewprivate = Horde::link($pgp_url->copy()->add('actionID', 'view_personal_private_key'), _("View Personal Private Key"), null, 'view_key');
                $view->infoprivate = Horde::link($pgp_url->copy()->add('actionID', 'info_personal_private_key'), _("Information on Personal Private Key"), null, 'info_key');
                $page_output->addInlineScript(array(
                    '$("delete_pgp_privkey").observe("click", function(e) { if (!window.confirm(' . json_encode(_("Are you sure you want to delete your keypair? (This is NOT recommended!)")) . ')) { e.stop(); } })'
                ), true);
            } else {
                $page_output->addScriptFile('prefs/pgp.js');
                Horde_Core_Ui_JsCalendar::init();
                $page_output->addInlineJsVars(array(
                    'ImpPgp.months' => Horde_Core_Ui_JsCalendar::months()
                ));

                $imp_identity = $injector->getInstance('IMP_Identity');
                $view->fullname = $imp_identity->getFullname();
                $view->fromaddr = $imp_identity->getFromAddress()->bare_address;

                if (!empty($conf['pgp']['keylength'])) {
                    $page_output->addInlineScript(array(
                        '$("create_pgp_key").observe("click", function(e) { if (!window.confirm(' . json_encode(_("Key generation may take a long time to complete.  Continue with key generation?")) . ')) { e.stop(); } })'
                    ), true);
                }

                if ($browser->allowFileUploads()) {
                    $view->import_pgp_private = true;
                    $page_output->addInlineScript(array(
                        '$("import_pgp_personal").observe("click", function(e) { ' . Horde::popupJs($pgp_url, array('params' => array('actionID' => 'import_personal_key', 'reload' => base64_encode($ui->selfUrl()->setRaw(true))), 'height' => 275, 'width' => 750, 'urlencode' => true)) . '; e.stop(); })'
                    ), true);
                }
            }
        }

        return $view->render('pgpprivatekey');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $conf, $injector, $notification;

        $imp_pgp = $injector->getInstance('IMP_Pgp');

        if (isset($ui->vars->delete_pgp_privkey)) {
            $imp_pgp->deletePersonalKeys();
            $notification->push(_("Personal PGP keys deleted successfully."), 'horde.success');
        } elseif (isset($ui->vars->create_pgp_key) &&
                  !empty($conf['pgp']['keylength'])) {
            /* Sanity checking for email address. */
            try {
                $email = IMP::parseAddressList($ui->vars->generate_email);
            } catch (Horde_Mail_Exception $e) {
                $notification->push($e);
                return false;
            }

            /* Check that fields are filled out (except for Comment) and that
             * the passphrases match. */
            if (empty($ui->vars->generate_realname) || empty($email)) {
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
                    $imp_pgp->generatePersonalKeys(
                        $ui->vars->generate_realname,
                        $email->first()->bare_address_idn,
                        $ui->vars->generate_passphrase1,
                        $ui->vars->generate_comment,
                        $conf['pgp']['keylength'],
                        $expire_date
                    );
                    $notification->push(_("Personal PGP keypair generated successfully."), 'horde.success');
                } catch (Exception $e) {
                    $notification->push($e);
                }
            }
        } elseif (isset($ui->vars->send_pgp_key)) {
            try {
                $imp_pgp->sendToPublicKeyserver($imp_pgp->getPersonalPublicKey());
                $notification->push(_("Key successfully sent to the public keyserver."), 'horde.success');
            } catch (Exception $e) {
                $notification->push($e);
            }
        } elseif (isset($ui->vars->unset_pgp_passphrase)) {
            $imp_pgp->unsetPassphrase('personal');
            $notification->push(_("PGP passphrase successfully unloaded."), 'horde.success');
        }

        return false;
    }

}
