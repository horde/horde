<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$thumbstyle = Horde_Util::getFormData('t');
$background = Horde_Util::getFormData('b');
$w = Horde_Util::getFormData('w');
$h = Horde_Util::getFormData('h');

// Create a dummy style object with only what is needed to generate
if ($thumbstyle || $background || $w || $h) {
    $style = new Ansel_Style(array('thumbstyle' => $thumbstyle,
                                   'background' => $background,
                                   'width' => $w,
                                   'height' => $h));
} else {
    $style = null;
}
$image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage(Horde_Util::getFormData('image'));
$gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery(abs($image->gallery));
if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ)) {
    throw new Horde_Exception_PermissionDenied(_("Access denied viewing this photo."));
}

/* Sendfile support. Lighttpd < 1.5 only understands the X-LIGHTTPD-send-file header */
if ($conf['vfs']['src'] == 'sendfile') {
    /* Need to ensure the file exists */
    try {
        $image->createView('thumb', $style);
    } catch (Horde_Exception $e) {
        Horde::logMessage($e, 'ERR');
        exit;
    }
    $filename = $injector->getInstance('Horde_Core_Factory_Vfs')->create('images')->readFile($image->getVFSPath('thumb', $style), $image->getVFSName('thumb'));
    header('Content-Type: ' . $image->getType('thumb'));
    header('X-LIGHTTPD-send-file: ' . $filename);
    header('X-Sendfile: ' . $filename);
    exit;
}

$image->display('thumb', $style);
