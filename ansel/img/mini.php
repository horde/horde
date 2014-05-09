<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$image = $storage->getImage(Horde_Util::getFormData('image'));
$gallery = $storage->getGallery(abs($image->gallery));
if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ)) {
    throw new Horde_Exception_PermissionDenied(_("Access denied viewing this photo."));
}

/* Sendfile support. Lighttpd < 1.5 only understands the X-LIGHTTPD-send-file header */
if ($conf['vfs']['src'] == 'sendfile') {
    /* Need to ensure the file exists */
    try {
        $image->createView('mini', Ansel::getStyleDefinition('ansel_default'));
    } catch (Ansel_Exception $e) {
        Horde::log($e, 'ERR');
        exit;
    }
    $filename = $injector->getInstance('Horde_Core_Factory_Vfs')->create('images')->readFile($image->getVFSPath('mini'), $image->getVFSName('mini'));
    Ansel::doSendFile($filename, $image->getType('mini'));
    exit;
}
$image->display('mini', Ansel::getStyleDefinition('ansel_default'));
