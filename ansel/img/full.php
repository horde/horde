<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$image = $ansel_storage->getImage(Horde_Util::getFormData('image'));
$gallery = $ansel_storage->getGallery($image->gallery);
if (!$gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ) || !$gallery->canDownload()) {
    throw new Horde_Exception_PermissionDenied(_("Access denied viewing this photo."));
}

/* Sendfile support. Lighttpd < 1.5 only understands the X-LIGHTTPD-send-file header */
if ($conf['vfs']['src'] == 'sendfile') {
    $filename = $ansel_vfs->readFile($image->getVFSPath('full'), $image->getVFSName('full'));
    header('Content-Type: ' . $image->getType('full'));
    header('X-LIGHTTPD-send-file: ' . $filename);
    header('X-Sendfile: ' . $filename);
    exit;
}
$image->display('full');
