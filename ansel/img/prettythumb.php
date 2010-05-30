<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$style = Horde_Util::getFormData('style');
$image = $ansel_storage->getImage(Horde_Util::getFormData('image'));
$gallery = $ansel_storage->getGallery(abs($image->gallery));
if (!$gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ)) {
    throw new Horde_Exception_PermissionDenied(_("Access denied viewing this photo."));
}

/* Sendfile support. Lighttpd < 1.5 only understands the X-LIGHTTPD-send-file header */
if ($conf['vfs']['src'] == 'sendfile') {
    /* Need to ensure the file exists */
    try {
        $image->createView('prettythumb', $style);
    } catch (Horde_Exception $e) {
        Horde::logMessage($e, 'ERR');
        exit;
    }
    $filename = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->readFile($image->getVFSPath('prettythumb', $style), $image->getVFSName('prettythumb'));
    header('Content-Type: ' . $image->getType('prettythumb'));
    header('X-LIGHTTPD-send-file: ' . $filename);
    header('X-Sendfile: ' . $filename);
    exit;
}

$image->display('prettythumb', $style);
