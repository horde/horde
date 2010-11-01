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

$image = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage(Horde_Util::getFormData('image'));
$gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($image->gallery);
if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) || !$gallery->canDownload()) {
    throw new Horde_Exception_PermissionDenied(_("Access denied viewing this photo."), __FILE__, __LINE__);
}
$image->downloadHeaders();

/* Sendfile support. Lighttpd < 1.5 only understands the X-LIGHTTPD-send-file header */
if ($conf['vfs']['src'] == 'sendfile') {
    $filename = $injector->getInstance('Horde_Core_Factory_Vfs')->create('images')->readFile($image->getVFSPath('full'), $image->getVFSName('full'));
    header('Content-Type: ' . $image->getType('full'));
    header('X-LIGHTTPD-send-file: ' . $filename);
    header('X-Sendfile: ' . $filename);
    exit;
}

$image->display('full');
