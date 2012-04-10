<?php
/**
 * S/MIME utilities.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp');

$imp_smime = $injector->getInstance('IMP_Crypt_Smime');
$vars = $injector->getInstance('Horde_Variables');

/* Run through the action handlers */
switch ($vars->actionID) {
case 'import_public_key':
    $imp_smime->importKeyDialog('process_import_public_key', $vars->reload);
    break;

case 'process_import_public_key':
    $error = false;
    try {
        $publicKey = $imp_smime->getImportKey($vars->import_key);

        /* Add the public key to the storage system. */
        $imp_smime->addPublicKey($publicKey);
        $notification->push(_("S/MIME public key successfully added."), 'horde.success');
        $imp_smime->reloadWindow($vars->reload);
    } catch (Horde_Browser_Exception $e) {
        $notification->push(_("No S/MIME public key imported."), 'horde.error');
        $error = true;
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $error = true;
    }

    if ($error) {
        $vars->actionID = 'import_public_key';
        $imp_smime->importKeyDialog('process_import_public_key', $vars->reload);
    }
    break;

case 'view_public_key':
case 'info_public_key':
    try {
        $key = $imp_smime->getPublicKey($vars->email);
    } catch (Horde_Exception $e) {
        $key = $e->getMessage();
    }
    if ($vars->actionID == 'view_public_key') {
        $imp_smime->textWindowOutput('S/MIME Public Key', $key);
    } else {
        $imp_smime->printCertInfo($key);
    }
    break;

case 'view_personal_public_key':
    $imp_smime->textWindowOutput('S/MIME Personal Public Key', $imp_smime->getPersonalPublicKey());
    break;

case 'info_personal_public_key':
    $imp_smime->printCertInfo($imp_smime->getPersonalPublicKey());
    break;

case 'view_personal_private_key':
    $imp_smime->textWindowOutput('S/MIME Personal Private Key', $imp_smime->getPersonalPrivateKey());
    break;

case 'import_personal_certs':
    $imp_smime->importKeyDialog('process_import_personal_certs', $vars->reload);
    break;

case 'process_import_personal_certs':
    try {
        $pkcs12 = $imp_smime->getImportKey($vars->import_key);
        $imp_smime->addFromPKCS12($pkcs12, $vars->upload_key_pass, $vars->upload_key_pk_pass);
        $notification->push(_("S/MIME Public/Private Keypair successfully added."), 'horde.success');
        $imp_smime->reloadWindow($vars->reload);
        $error = false;
    } catch (Horde_Browser_Exception $e) {
        $notification->push(_("Personal S/MIME certificates NOT imported."), 'horde.error');
        $error = true;
    } catch (Horde_Exception $e) {
        $notification->push(_("Personal S/MIME certificates NOT imported: ") . $e->getMessage(), 'horde.error');
        $error = true;
    }

    if ($error) {
        $vars->actionID = 'import_personal_certs';
        $imp_smime->importKeyDialog('process_import_personal_certs', $vars->reload);
    }
    break;
}
