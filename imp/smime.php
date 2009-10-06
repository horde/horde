<?php
/**
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

function _importKeyDialog($target)
{
    $title = _("Import S/MIME Key");
    require IMP_TEMPLATES . '/common-header.inc';
    IMP::status();

    $t = new Horde_Template();
    $t->setOption('gettext', true);
    $t->set('selfurl', Horde::applicationUrl('smime.php'));
    $t->set('broken_mp_form', $GLOBALS['browser']->hasQuirk('broken_multipart_form'));
    $t->set('reload', htmlspecialchars(Horde_Util::getFormData('reload')));
    $t->set('target', $target);
    $t->set('forminput', Horde_Util::formInput());
    $t->set('import_public_key', $target == 'process_import_public_key');
    $t->set('import_personal_certs', $target == 'process_import_personal_certs');
    echo $t->fetch(IMP_TEMPLATES . '/smime/import_key.html');
}

function _getImportKey()
{
    $key = Horde_Util::getFormData('import_key');
    if (!empty($key)) {
        return $key;
    }

    $res = $GLOBALS['browser']->wasFileUploaded('upload_key', _("key"));
    if ($res instanceof PEAR_Error) {
        $GLOBALS['notification']->push($res, 'horde.error');
        return;
    }

    return file_get_contents($_FILES['upload_key']['tmp_name']);
}

function _actionWindow()
{
    $oid = Horde_Util::getFormData('passphrase_action');
    $cacheSess = Horde_SessionObjects::singleton();
    $code = $cacheSess->query($oid);
    $cacheSess->prune($oid);
    Horde_Util::closeWindowJS($code);
}

function _reloadWindow()
{
    $reload = Horde_Util::getFormData('reload');
    $cacheSess = Horde_SessionObjects::singleton();
    $href = $cacheSess->query($reload);
    $cacheSess->prune($reload);
    Horde_Util::closeWindowJS('opener.focus();opener.location.href="' . $href . '";');
}

function _textWindowOutput($filename, $msg, $html = false)
{
    $type = ($html ? 'text/html' : 'text/plain') . '; charset=' . Horde_Nls::getCharset();
    $GLOBALS['browser']->downloadHeaders($filename, $type, true, strlen($msg));
    echo $msg;
}

function _printKeyInfo($cert)
{
    $key_info = $GLOBALS['imp_smime']->certToHTML($cert);
    if (empty($key_info)) {
        _textWindowOutput('S/MIME Key Information', _("Invalid key"));
    } else {
        _textWindowOutput('S/MIME Key Information', $key_info, true);
    }
}


require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true));

$imp_smime = Horde_Crypt::singleton(array('IMP', 'Smime'));
$secure_check = Horde::isConnectionSecure();

/* Run through the action handlers */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'delete_key':
    $imp_smime->deletePersonalKeys();
    $notification->push(_("Personal S/MIME keys deleted successfully."), 'horde.success');
    break;

case 'delete_public_key':
    try {
        $imp_smime->deletePublicKey(Horde_Util::getFormData('email'));
        $notification->push(sprintf(_("S/MIME Public Key for \"%s\" was successfully deleted."), Horde_Util::getFormData('email')), 'horde.success');
    } catch (Horde_Exception $e) {
        $notification->push($e);
    }
    break;

case 'import_public_key':
    _importKeyDialog('process_import_public_key');
    exit;

case 'process_import_public_key':
    $publicKey = _getImportKey();
    if (empty($publicKey)) {
        $notification->push(_("No S/MIME public key imported."), 'horde.error');
        $actionID = 'import_public_key';
        _importKeyDialog('process_import_public_key');
    } else {
        /* Add the public key to the storage system. */
        try {
            $imp_smime->addPublicKey($publicKey);
            $notification->push(_("S/MIME Public Key successfully added."), 'horde.success');
            _reloadWindow();
        } catch (Horde_Exception $e) {
            $notification->push($e, 'horde.error');
            $actionID = 'import_public_key';
            _importKeyDialog('process_import_public_key');
        }
    }
    exit;

case 'view_public_key':
case 'info_public_key':
    try {
        $key = $imp_smime->getPublicKey(Horde_Util::getFormData('email'));
    } catch (Horde_Exception $e) {
        $key = $e->getMessage();
    }
    if ($actionID == 'view_public_key') {
        _textWindowOutput('S/MIME Public Key', $key);
    } else {
        _printKeyInfo($key);
    }
    exit;

case 'view_personal_public_key':
    _textWindowOutput('S/MIME Personal Public Key', $imp_smime->getPersonalPublicKey());
    exit;
case 'info_personal_public_key':
    _printKeyInfo($imp_smime->getPersonalPublicKey());
    exit;

case 'view_personal_private_key':
    _textWindowOutput('S/MIME Personal Private Key', $imp_smime->getPersonalPrivateKey());
    exit;

case 'import_personal_certs':
    _importKeyDialog('process_import_personal_certs');
    exit;

case 'process_import_personal_certs':
    if (!($pkcs12 = _getImportKey())) {
        $notification->push(_("No personal S/MIME certificates imported."), 'horde.error');
        $actionID = 'import_personal_certs';
        _importKeyDialog('process_import_personal_certs');
    } else {
        try {
            $imp_smime->addFromPKCS12($pkcs12, Horde_Util::getFormData('upload_key_pass'), Horde_Util::getFormData('upload_key_pk_pass'));
            $notification->push(_("S/MIME Public/Private Keypair successfully added."), 'horde.success');
            _reloadWindow();
        } catch (Horde_Exception $e) {
            $notification->push(_("Personal S/MIME certificates NOT imported: ") . $e->getMessage(), 'horde.error');
            $actionID = 'import_personal_certs';
            _importKeyDialog('process_import_personal_certs');
        }
    }
    exit;

case 'save_attachment_public_key':
    /* Retrieve the key from the message. */
    $contents = IMP_Contents::singleton(Horde_Util::getFormData('uid') . IMP::IDX_SEP . Horde_Util::getFormData('mailbox'));
    $mime_part = $contents->getMIMEPart(Horde_Util::getFormData('mime_id'));
    if (empty($mime_part)) {
        throw new Horde_Exception('Cannot retrieve public key from message.');
    }

    /* Add the public key to the storage system. */
    try {
        $imp_smime->addPublicKey($mime_part);
        Horde_Util::closeWindowJS();
    } catch (Horde_Exception $e) {
        $notification->push(_("No Certificate found"), 'horde.error');
    }
    exit;

case 'unset_passphrase':
    if ($imp_smime->getPassphrase() !== false) {
        $imp_smime->unsetPassphrase();
        $notification->push(_("Passphrase successfully unloaded."), 'horde.success');
    }
    break;

case 'save_options':
    $prefs->setValue('use_smime', Horde_Util::getFormData('use_smime') ? 1 : 0);
    $prefs->setValue('smime_verify', Horde_Util::getFormData('smime_verify') ? 1 : 0);
    $notification->push(_("Preferences successfully updated."), 'horde.success');
    break;
}

/* Get list of Public Keys. */
try {
    $pubkey_list = $imp_smime->listPublicKeys();
} catch (Horde_Exception $e) {
    $pubkey_list = array();
    $notification->push($e);
}

extract(Horde::loadConfiguration('prefs.php', array('prefGroups', '_prefs'), 'imp'));

require_once 'Horde/Prefs/UI.php';
$app = 'imp';
$chunk = Horde_Util::nonInputVar('chunk');
Prefs_UI::generateHeader('smime', $chunk);

$selfURL = Horde::applicationUrl('smime.php');

/* If S/MIME preference not active, or openssl PHP extension not available, do
 * NOT show S/MIME Admin screen. */
try {
    $imp_smime->checkForOpenSSL();
    $openssl_check = true;
} catch (Horde_Exception $e) {
    $openssl_check = false;
}

/* If S/MIME preference not active, do NOT show S/MIME Admin screen. */
$t = new Horde_Template();
$t->setOption('gettext', true);
$t->set('use_smime_help', Horde_Help::link('imp', 'smime-overview'));
if ($openssl_check && $prefs->getValue('use_smime')) {
    Horde::addScriptFile('imp.js', 'imp');
    $t->set('smimeactive', true);
    $t->set('manage_pubkey-help', Horde_Help::link('imp', 'smime-manage-pubkey'));

    $t->set('verify_notlocked', !$prefs->isLocked('smime_verify'));
    if ($t->get('verify_notlocked')) {
        $t->set('smime_verify', $prefs->getValue('smime_verify'));
        $t->set('smime_verify-help', Horde_Help::link('imp', 'smime-option-verify'));
    }

    $t->set('empty_pubkey_list', empty($pubkey_list));
    if (!$t->get('empty_pubkey_list')) {
        $plist = array();
        foreach ($pubkey_list as $val) {
            $linkurl = Horde_Util::addParameter($selfURL, 'email', $val['email']);
            $plist[] = array(
                'name' => $val['name'],
                'email' => $val['email'],
                'view' => Horde::link(Horde_Util::addParameter($linkurl, 'actionID', 'view_public_key'), sprintf(_("View %s Public Key"), $val['name']), null, 'view_key'),
                'info' => Horde::link(Horde_Util::addParameter($linkurl, 'actionID', 'info_public_key'), sprintf(_("Information on %s Public Key"), $val['name']), null, 'info_key'),
                'delete' => Horde::link(Horde_Util::addParameter($linkurl, 'actionID', 'delete_public_key'), sprintf(_("Delete %s Public Key"), $val['name']), null, null, "if (confirm('" . addslashes(_("Are you sure you want to delete this public key?")) . "')) { return true; } else { return false; }")
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
            $t->set('import_pubkey-help', Horde_Help::link('imp', 'smime-import-pubkey'));
        }
    }
    $t->set('personalkey-help', Horde_Help::link('imp', 'smime-overview-personalkey'));

    $t->set('secure_check', $secure_check instanceof PEAR_Error);
    if (!$t->get('secure_check')) {
        $t->set('has_key', $prefs->getValue('smime_public_key') && $prefs->getValue('smime_private_key'));
        if ($t->get('has_key')) {
            $t->set('viewpublic', Horde::link(Horde_Util::addParameter($selfURL, 'actionID', 'view_personal_public_key'), _("View Personal Public Key"), null, 'view_key'));
            $t->set('infopublic', Horde::link(Horde_Util::addParameter($selfURL, 'actionID', 'info_personal_public_key'), _("Information on Personal Public Key"), null, 'info_key'));
            $passphrase = $imp_smime->getPassphrase();
            $t->set('passphrase', empty($passphrase) ? Horde::link('#', _("Enter Passphrase"), null, null, IMP::passphraseDialogJS('SMIMEPersonal') . ';return false;') . _("Enter Passphrase") : Horde::link(Horde_Util::addParameter($selfURL, 'actionID', 'unset_passphrase'), _("Unload Passphrase")) . _("Unload Passphrase"));
            $t->set('viewprivate', Horde::link(Horde_Util::addParameter($selfURL, 'actionID', 'view_personal_private_key'), _("View Personal Private Key"), null, 'view_key'));
            $t->set('deletekeypair', addslashes(_("Are you sure you want to delete your keypair? (This is NOT recommended!)")));
            $t->set('personalkey-delete-help', Horde_Help::link('imp', 'smime-delete-personal-certs'));
        } else {
            $t->set('personal_import_url', Horde::popupJs($selfURL, array('params' => array('actionID' => 'import_personal_certs'), 'height' => 275, 'width' => 750, 'urlencode' => true)));
            $t->set('import-cert-help', Horde_Help::link('imp', 'smime-import-personal-certs'));
        }
    }
} else {
    $t->set('use_smime_locked', $prefs->isLocked('use_smime'));
    if (!$t->get('use_smime_locked')) {
        $t->set('use_smime_label', Horde::label('use_smime', _("Enable S/MIME functionality?")));
    }
}
$t->set('prefsurl', Horde::getServiceLink('options', 'imp'));

echo $t->fetch(IMP_TEMPLATES . '/smime/smime.html');
if (!$chunk) {
    require $registry->get('templates', 'horde') . '/common-footer.inc';
}
