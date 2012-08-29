<?php
/**
 * PGP utilities.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp');

$imp_pgp = $injector->getInstance('IMP_Crypt_Pgp');
$secure_check = Horde::isConnectionSecure();
$vars = $injector->getInstance('Horde_Variables');

/* Run through the action handlers */
switch ($vars->actionID) {
case 'import_public_key':
    $imp_pgp->importKeyDialog('process_import_public_key', $vars->reload);
    break;

case 'process_import_public_key':
    $import_keys = $imp_pgp->getKeys($vars->import_key);
    try {
        $browser->wasFileUploaded('upload_key', _("key"));
        $import_keys = array_merge_recursive($import_keys, $imp_pgp->getKeys(file_get_contents($_FILES['upload_key']['tmp_name'])));
    } catch (Horde_Browser_Exception $e) {}

    if (count($import_keys['public'])) {
        foreach ($import_keys['public'] as $val) {
            $key_info = $imp_pgp->addPublicKey($val);
            foreach ($key_info['signature'] as $sig) {
                $notification->push(sprintf(_("PGP Public Key for \"%s (%s)\" was successfully added."), $sig['name'], $sig['email']), 'horde.success');
            }
        }
        $imp_pgp->reloadWindow($vars->reload);
    } else {
        $notification->push(_("No PGP public key imported."), 'horde.error');
        $vars->actionID = 'import_public_key';
        $imp_pgp->importKeyDialog('process_import_public_key', $vars->reload);
    }
    break;

case 'import_personal_key':
    $imp_pgp->importKeyDialog('process_import_personal_key', $vars->reload);
    break;

case 'process_import_personal_key':
    $import_keys = $imp_pgp->getKeys($vars->import_key);

    if (empty($import_keys['public']) || empty($import_keys['private'])) {
        try {
            $browser->wasFileUploaded('upload_key', _("key"));
            $import_keys = array_merge_recursive($import_keys, $imp_pgp->getKeys(file_get_contents($_FILES['upload_key']['tmp_name'])));
        } catch (Horde_Browser_Exception $e) {
            if ($e->getCode() != UPLOAD_ERR_NO_FILE) {
                $notification->push($e, 'horde.error');
            }
        }
    }

    if (!empty($import_keys['public']) && !empty($import_keys['private'])) {
        $imp_pgp->addPersonalPublicKey($import_keys['public'][0]);
        $imp_pgp->addPersonalPrivateKey($import_keys['private'][0]);
        $notification->push(_("Personal PGP key successfully added."), 'horde.success');
        $imp_pgp->reloadWindow($vars->reload);
    } else {
        if (empty($import_keys['public'])) {
            $notification->push(_("No personal PGP public key imported."), 'horde.error');
        }
        if (empty($import_keys['private'])) {
            $notification->push(_("No personal PGP private key imported."), 'horde.error');
        }

        $vars->actionID = 'import_personal_key';
        $imp_pgp->importKeyDialog('process_import_personal_key', $vars->reload);
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
}
