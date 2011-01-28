<?php
/**
 * PGP utilities.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

$imp_pgp = $injector->getInstance('IMP_Crypt_Pgp');
$secure_check = Horde::isConnectionSecure();
$vars = Horde_Variables::getDefaultVariables();

/* Run through the action handlers */
switch ($vars->actionID) {
case 'import_public_key':
    $imp_pgp->importKeyDialog('process_import_public_key', $vars->reload);
    break;

case 'process_import_public_key':
    try {
        $publicKey = $imp_pgp->getImportKey($vars->import_key);
        /* Add the public key to the storage system. */
        $key_info = $imp_pgp->addPublicKey($publicKey);
        foreach ($key_info['signature'] as $sig) {
            $notification->push(sprintf(_("PGP Public Key for \"%s (%s)\" was successfully added."), $sig['name'], $sig['email']), 'horde.success');
        }
        $imp_pgp->reloadWindow($vars->reload);
        $error = false;
    } catch (Horde_Browser_Exception $e) {
        $notification->push(_("No PGP public key imported."), 'horde.error');
        $error = true;
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $error = true;
    }

    if ($error) {
        $vars->actionID = 'import_public_key';
        $imp_pgp->importKeyDialog('process_import_public_key', $vars->reload);
    }
    break;

case 'import_personal_public_key':
    $imp_pgp->importKeyDialog('process_import_personal_public_key', $vars->reload);
    break;

case 'process_import_personal_public_key':
    /* Check the public key. */
    $error = false;
    try {
        $publicKey = $imp_pgp->getImportKey($vars->import_key);
        if (($key_info = $imp_pgp->pgpPacketInformation($publicKey)) &&
            isset($key_info['public_key'])) {
            if (isset($key_info['secret_key'])) {
                /* Key contains private key too, don't allow to add this
                 * as public key. */
                $notification->push(_("Imported key contains your PGP private key. Only add your public key in the first step!"), 'horde.error');
                $error = true;
            } else {
                /* Success in importing public key - Move on to private
                 * key now. */
                $imp_pgp->addPersonalPublicKey($publicKey);
                $notification->push(_("PGP public key successfully added."), 'horde.success');
                $vars->actionID = 'import_personal_private_key';
                $imp_pgp->importKeyDialog('process_import_personal_private_key', $vars->reload);
            }
        } else {
            /* Invalid public key imported - Redo public key import
             * screen. */
            $notification->push(_("Invalid personal PGP public key."), 'horde.error');
            $error = true;
        }
    } catch (Horde_Browser_Exception $e) {
        $notification->push(_("No personal PGP public key imported."), 'horde.error');
        $error = true;
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $error = true;
    }

    if ($error) {
        $vars->actionID = 'import_personal_public_key';
        $imp_pgp->importKeyDialog('process_import_personal_public_key', $vars->reload);
    }
    break;

case 'process_import_personal_private_key':
    /* Check the private key. */
    $error = false;
    try {
        $privateKey = $imp_pgp->getImportKey($vars->import_key);
        if (($key_info = $imp_pgp->pgpPacketInformation($privateKey)) &&
            isset($key_info['secret_key'])) {
            /* Personal public and private keys have been imported
             * successfully - close the import popup window. */
            $imp_pgp->addPersonalPrivateKey($privateKey);
            $notification->push(_("PGP private key successfully added."), 'horde.success');
            $imp_pgp->reloadWindow($vars->reload);
        } else {
            /* Invalid private key imported - Redo private key import
             * screen. */
            $notification->push(_("Invalid personal PGP private key."), 'horde.error');
            $error = true;
        }
    } catch (Horde_Browser_Exception $e) {
        $notification->push(_("No personal PGP private key imported."), 'horde.error');
        $error = true;
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $error = true;
    }

    if ($error) {
        $vars->actionID = 'import_personal_private_key';
        $imp_pgp->importKeyDialog('process_import_personal_private_key', $vars->reload);
    }
    break;

case 'view_public_key':
case 'info_public_key':
    try {
        $key = $imp_pgp->getPublicKey($vars->email, array('noserver' => true));
    } catch (Horde_Exception $e) {
        $key = $e->getMessage();
    }
    if ($vars->actionID == 'view_public_key') {
        $imp_pgp->textWindowOutput('PGP Public Key', $key);
    } else {
        $imp_pgp->printKeyInfo($key);
    }
    break;

case 'view_personal_public_key':
    $imp_pgp->textWindowOutput('PGP Personal Public Key', $imp_pgp->getPersonalPublicKey());
    break;

case 'info_personal_public_key':
    $imp_pgp->printKeyInfo($imp_pgp->getPersonalPublicKey());
    break;

case 'view_personal_private_key':
    $imp_pgp->textWindowOutput('PGP Personal Private Key', $imp_pgp->getPersonalPrivateKey());
    break;

case 'info_personal_private_key':
    $imp_pgp->printKeyInfo($imp_pgp->getPersonalPrivateKey());
    break;

case 'save_attachment_public_key':
    /* Retrieve the key from the message. */
    $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($vars->mailbox, $vars->uid));
    $mime_part = $contents->getMIMEPart($vars->mime_id);
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
    break;
}
