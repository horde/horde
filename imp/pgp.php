<?php
/**
 * PGP preferences handling.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

$imp_pgp = Horde_Crypt::singleton(array('IMP', 'Pgp'));
$secure_check = Horde::isConnectionSecure();

/* Run through the action handlers */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'generate_key':
    /* Check that fields are filled out (except for Comment) and that the
       passphrases match. */
    $realname = Horde_Util::getFormData('generate_realname');
    $email = Horde_Util::getFormData('generate_email');
    $comment = Horde_Util::getFormData('generate_comment');
    $keylength = Horde_Util::getFormData('generate_keylength');
    $passphrase1 = Horde_Util::getFormData('generate_passphrase1');
    $passphrase2 = Horde_Util::getFormData('generate_passphrase2');

    if (empty($realname) || empty($email)) {
        $notification->push(_("Name and/or email cannot be empty"), 'horde.error');
    } elseif (empty($passphrase1) || empty($passphrase2)) {
        $notification->push(_("Passphrases cannot be empty"), 'horde.error');
    } elseif ($passphrase1 !== $passphrase2) {
        $notification->push(_("Passphrases do not match"), 'horde.error');
    } else {
        try {
            $imp_pgp->generatePersonalKeys($realname, $email, $passphrase1, $comment, $keylength);
            $notification->push(_("Personal PGP keypair generated successfully."), 'horde.success');
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }
    break;

case 'delete_key':
    $imp_pgp->deletePersonalKeys();
    $notification->push(_("Personal PGP keys deleted successfully."), 'horde.success');
    break;

case 'import_public_key':
    $imp_pgp->importKeyDialog('process_import_public_key', Horde_Util::getFormData('reload'));
    exit;

case 'process_import_public_key':
    try {
        $publicKey = $imp_pgp->getImportKey(Horde_Util::getFormData('import_key'));
        /* Add the public key to the storage system. */
        $key_info = $imp_pgp->addPublicKey($publicKey);
        foreach ($key_info['signature'] as $sig) {
            $notification->push(sprintf(_("PGP Public Key for \"%s (%s)\" was successfully added."), $sig['name'], $sig['email']), 'horde.success');
        }
        $imp_pgp->reloadWindow(Horde_Util::getFormData('reload'));
    } catch (Horde_Browser_Exception $e) {
        $notification->push(_("No PGP public key imported."), 'horde.error');
        throw new Horde_Exception_Prior($e);
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $actionID = 'import_public_key';
        $imp_pgp->importKeyDialog('process_import_public_key', Horde_Util::getFormData('reload'));
    }
    exit;

case 'import_personal_public_key':
    $imp_pgp->importKeyDialog('process_import_personal_public_key', Horde_Util::getFormData('reload'));
    exit;

case 'process_import_personal_public_key':
    $actionID = 'import_personal_public_key';
    /* Check the public key. */
    try {
        $publicKey = $imp_pgp->getImportKey(Horde_Util::getFormData('import_key'));
        if (($key_info = $imp_pgp->pgpPacketInformation($publicKey)) &&
            isset($key_info['public_key'])) {
            if (isset($key_info['secret_key'])) {
                /* Key contains private key too, don't allow to add this
                 * as public key. */
                $notification->push(_("Imported key contains your PGP private key. Only add your public key in the first step!"), 'horde.error');
                $imp_pgp->importKeyDialog('process_import_personal_public_key', Horde_Util::getFormData('reload'));
            } else {
                /* Success in importing public key - Move on to private
                 * key now. */
                $imp_pgp->addPersonalPublicKey($publicKey);
                $notification->push(_("PGP public key successfully added."), 'horde.success');
                $actionID = 'import_personal_private_key';
                $imp_pgp->importKeyDialog('process_import_personal_private_key', Horde_Util::getFormData('reload'));
            }
        } else {
            /* Invalid public key imported - Redo public key import
             * screen. */
            $notification->push(_("Invalid personal PGP public key."), 'horde.error');
            $imp_pgp->importKeyDialog('process_import_personal_public_key', Horde_Util::getFormData('reload'));
        }
    } catch (Horde_Browser_Exception $e) {
        $notification->push(_("No personal PGP public key imported."), 'horde.error');
        throw new Horde_Exception_Prior($e);
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $imp_pgp->importKeyDialog('process_import_personal_public_key', Horde_Util::getFormData('reload'));
    }
    exit;

case 'process_import_personal_private_key':
    $actionID = 'import_personal_private_key';
    /* Check the private key. */
    try {
        $privateKey = $imp_pgp->getImportKey(Horde_Util::getFormData('import_key'));
        if (($key_info = $imp_pgp->pgpPacketInformation($privateKey)) &&
            isset($key_info['secret_key'])) {
            /* Personal public and private keys have been imported
             * successfully - close the import popup window. */
            $imp_pgp->addPersonalPrivateKey($privateKey);
            $notification->push(_("PGP private key successfully added."), 'horde.success');
            $imp_pgp->reloadWindow(Horde_Util::getFormData('reload'));
        } else {
            /* Invalid private key imported - Redo private key import
             * screen. */
            $notification->push(_("Invalid personal PGP private key."), 'horde.error');
            $imp_pgp->importKeyDialog('process_import_personal_private_key', Horde_Util::getFormData('reload'));
        }
    } catch (Horde_Browser_Exception $e) {
        $notification->push(_("No personal PGP private key imported."), 'horde.error');
        throw new Horde_Exception_Prior($e);
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $imp_pgp->importKeyDialog('process_import_personal_private_key', Horde_Util::getFormData('reload'));
    }
    exit;

case 'view_public_key':
case 'info_public_key':
    try {
        $key = $imp_pgp->getPublicKey(Horde_Util::getFormData('email'), array('noserver' => true));
    } catch (Horde_Exception $e) {
        $key = $e->getMessage();
    }
    if ($actionID == 'view_public_key') {
        $imp_pgp->textWindowOutput('PGP Public Key', $key);
    } else {
        $imp_pgp->printKeyInfo($key);
    }
    exit;

case 'view_personal_public_key':
    $imp_pgp->textWindowOutput('PGP Personal Public Key', $imp_pgp->getPersonalPublicKey());
    exit;

case 'info_personal_public_key':
    $imp_pgp->printKeyInfo($imp_pgp->getPersonalPublicKey());
    exit;

case 'view_personal_private_key':
    $imp_pgp->textWindowOutput('PGP Personal Private Key', $imp_pgp->getPersonalPrivateKey());
    exit;

case 'info_personal_private_key':
    $imp_pgp->printKeyInfo($imp_pgp->getPersonalPrivateKey());
    exit;

case 'delete_public_key':
    try {
        $imp_pgp->deletePublicKey(Horde_Util::getFormData('email'));
        $notification->push(sprintf(_("PGP Public Key for \"%s\" was successfully deleted."), Horde_Util::getFormData('email')), 'horde.success');
    } catch (Horde_Exception $e) {
        $notification->push($e);
    }
    break;

case 'save_options':
    $prefs->setValue('use_pgp', Horde_Util::getFormData('use_pgp') ? 1 : 0);
    $prefs->setValue('pgp_attach_pubkey', Horde_Util::getFormData('pgp_attach_pubkey') ? 1 : 0);
    $prefs->setValue('pgp_scan_body', Horde_Util::getFormData('pgp_scan_body') ? 1 : 0);
    $prefs->setValue('pgp_verify', Horde_Util::getFormData('pgp_verify') ? 1 : 0);
    $notification->push(_("Preferences successfully updated."), 'horde.success');
    break;

case 'save_attachment_public_key':
    /* Retrieve the key from the message. */
    $contents = IMP_Contents::singleton(Horde_Util::getFormData('uid') . IMP::IDX_SEP . Horde_Util::getFormData('mailbox'));
    $mime_part = $contents->getMIMEPart(Horde_Util::getFormData('mime_id'));
    if (empty($mime_part)) {
        throw new IMP_Exception('Cannot retrieve public key from message.');
    }

    /* Add the public key to the storage system. */
    try {
        $imp_pgp->addPublicKey($mime_part->getContents());
        echo Horde::wrapInlineScript(array('window.close();'));
    } catch (Horde_Exception $e) {
        $notification->push($e, $key_info->getCode());
    }
    exit;

case 'unset_passphrase':
    $imp_pgp->unsetPassphrase('personal');
    $notification->push(_("Passphrase successfully unloaded."), 'horde.success');
    break;

case 'send_public_key':
    try {
        $imp_pgp->sendToPublicKeyserver($imp_pgp->getPersonalPublicKey());
        $notification->push(_("Key successfully sent to the public keyserver."), 'horde.success');
    } catch (Horde_Exception $e) {
        $notification->push($e);
    }
    break;
}

$selfURL = Horde::applicationUrl('pgp.php');

/* Get list of Public Keys on keyring. */
try {
    $pubkey_list = $imp_pgp->listPublicKeys();
} catch (Horde_Exception $e) {
    $pubkey_list = array();
    $notification->push($e);
}

$chunk = Horde_Util::nonInputVar('chunk');
Horde_Prefs_Ui::generateHeader('imp', null, 'pgp', $chunk);

/* If PGP preference not active, do NOT show PGP Admin screen. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
if ($prefs->getValue('use_pgp')) {
    Horde::addScriptFile('imp.js', 'imp');
    $t->set('pgpactive', true);
    $t->set('overview-help', Horde_Help::link('imp', 'pgp-overview'));
    $t->set('attach_pubkey_notlocked', !$prefs->isLocked('pgp_attach_pubkey'));
    if ($t->get('attach_pubkey_notlocked')) {
        $t->set('attach_pubkey', $prefs->getValue('pgp_attach_pubkey'));
        $t->set('attach_pubkey-help', Horde_Help::link('imp', 'pgp-option-attach-pubkey'));
    }
    $t->set('scan_body_notlocked', !$prefs->isLocked('pgp_scan_body'));
    if ($t->get('scan_body_notlocked')) {
        $t->set('scan_body', $prefs->getValue('pgp_scan_body'));
        $t->set('scan_body-help', Horde_Help::link('imp', 'pgp-option-scan-body'));
    }
    $t->set('verify_notlocked', !$prefs->isLocked('pgp_verify'));
    if ($t->get('verify_notlocked')) {
        $t->set('pgp_verify', $prefs->getValue('pgp_verify'));
        $t->set('pgp_verify-help', Horde_Help::link('imp', 'pgp-option-verify'));
    }
    $t->set('manage_pubkey-help', Horde_Help::link('imp', 'pgp-manage-pubkey'));

    $t->set('empty_pubkey_list', empty($pubkey_list));
    if (!$t->get('empty_pubkey_list')) {
        $plist = array();
        foreach ($pubkey_list as $val) {
            $linkurl = $selfURL->copy()->add('email', $val['email']);
            $plist[] = array(
                'name' => $val['name'],
                'email' => $val['email'],
                'view' => Horde::link($linkurl->copy()->add('actionID', 'view_public_key'), sprintf(_("View %s Public Key"), $val['name']), null, 'view_key'),
                'info' => Horde::link($linkurl->copy()->add('actionID', 'info_public_key'), sprintf(_("Information on %s Public Key"), $val['name']), null, 'info_key'),
                'delete' => Horde::link($linkurl->copy()->add('actionID', 'delete_public_key'), sprintf(_("Delete %s Public Key"), $val['name']), null, null, "if (confirm('" . addslashes(_("Are you sure you want to delete this public key?")) . "')) { return true; } else { return false; }")
            );
        }
        $t->set('pubkey_list', $plist);
    }

    $t->set('no_file_upload', !$_SESSION['imp']['file_upload']);
    if (!$t->get('no_file_upload')) {
        $t->set('no_source', !$GLOBALS['prefs']->getValue('add_source'));
        if (!$t->get('no_source')) {
            $cacheSess = Horde_SessionObjects::singleton();
            $t->set('public_import_url', Horde::popupJs($selfURL, array('params' => array('actionID' => 'import_public_key', 'reload' => $cacheSess->storeOid($selfURL, false)), 'height' => 275, 'width' => 750, 'urlencode' => true)));
            $t->set('import_pubkey-help', Horde_Help::link('imp', 'pgp-import-pubkey'));
        }
    }
    $t->set('personalkey-help', Horde_Help::link('imp', 'pgp-overview-personalkey'));

    $t->set('secure_check', !$secure_check);
    if ($secure_check) {
        $t->set('has_key', $prefs->getValue('pgp_public_key') && $prefs->getValue('pgp_private_key'));
        if ($t->get('has_key')) {
            $t->set('viewpublic', Horde::link($selfURL->copy()->add('actionID', 'view_personal_public_key'), _("View Personal Public Key"), null, 'view_key'));
            $t->set('infopublic', Horde::link($selfURL->copy()->add('actionID', 'info_personal_public_key'), _("Information on Personal Public Key"), null, 'info_key'));
            $t->set('sendkey', Horde::link($selfURL->copy()->add('actionID', 'send_public_key'), _("Send Key to Public Keyserver")));
            $t->set('personalkey-public-help', Horde_Help::link('imp', 'pgp-personalkey-public'));

            $imple = Horde_Ajax_Imple::factory(array('imp', 'PassphraseDialog'), array('type' => 'pgpPersonal'));
            $imple->attach();
            $passphrase = $imp_pgp->getPassphrase('personal');
            $t->set('passphrase', (empty($passphrase)) ? Horde::link('#', _("Enter Passphrase"), null, null, null, null, null, array('id' => $imple->getPassphraseId())) . _("Enter Passphrase") : Horde::link($selfURL->copy()->add('actionID', 'unset_passphrase'), _("Unload Passphrase")) . _("Unload Passphrase"));

            $t->set('viewprivate', Horde::link($selfURL->copy()->add('actionID', 'view_personal_private_key'), _("View Personal Private Key"), null, 'view_key'));
            $t->set('infoprivate', Horde::link($selfURL->copy()->add('actionID', 'info_personal_private_key'), _("Information on Personal Private Key"), null, 'info_key'));
            $t->set('personalkey-private-help', Horde_Help::link('imp', 'pgp-personalkey-private'));
            $t->set('deletekeypair', addslashes(_("Are you sure you want to delete your keypair? (This is NOT recommended!)")));
            $t->set('personalkey-delete-help', Horde_Help::link('imp', 'pgp-personalkey-delete'));
        } else {
            $imp_identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));
            $t->set('fullname', $imp_identity->getFullname());
            $t->set('personalkey-create-name-help', Horde_Help::link('imp', 'pgp-personalkey-create-name'));
            $t->set('personalkey-create-comment-help', Horde_Help::link('imp', 'pgp-personalkey-create-comment'));
            $t->set('fromaddr', $imp_identity->getFromAddress());
            $t->set('personalkey-create-email-help', Horde_Help::link('imp', 'pgp-personalkey-create-email'));
            $t->set('personalkey-create-keylength-help', Horde_Help::link('imp', 'pgp-personalkey-create-keylength'));
            $t->set('personalkey-create-passphrase-help', Horde_Help::link('imp', 'pgp-personalkey-create-passphrase'));
            $t->set('keygen', addslashes(_("Key generation may take a long time to complete.  Continue with key generation?")));
            $t->set('personal_import_url', Horde::popupJs($selfURL, array('params' => array('actionID' => 'import_personal_public_key'), 'height' => 275, 'width' => 750, 'urlencode' => true)));
            $t->set('personalkey-create-actions-help', Horde_Help::link('imp', 'pgp-personalkey-create-actions'));
        }
    }

} else {
    $t->set('use_pgp_locked', $prefs->isLocked('use_pgp'));
    if (!$t->get('use_pgp_locked')) {
        $t->set('use_pgp_label', Horde::label('use_pgp', _("Enable PGP functionality?")));
        $t->set('use_pgp_help', Horde_Help::link('imp', 'pgp-overview'));
    }
}
$t->set('prefsurl', Horde::getServiceLink('options', 'imp'));

echo $t->fetch(IMP_TEMPLATES . '/prefs/pgp/pgp.html');
if (!$chunk) {
    require $registry->get('templates', 'horde') . '/common-footer.inc';
}
