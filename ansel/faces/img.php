<?php
/**
 * Fetch face image for display
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$face_id = Horde_Util::getFormData('face');
$faces = $GLOBALS['injector']->getInstance('Ansel_Faces');

// Sendfile support. Lighttpd < 1.5 only understands the X-LIGHTTPD-send-file
// header
if ($conf['vfs']['src'] == 'sendfile') {
    $face = $faces->getFaceById($face_id);

    // Make sure the view exists
    if (!$faces->viewExists($face['image_id'], $face_id, true)) {
        Horde::logMessage(sprintf('Unable to locate or create face_id %u.',
                                  $face_id));
        exit;
    }

    // We definitely have an image for the face.
    try {
        $filename = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->readFile(
            Ansel_Faces::getVFSPath($face['image_id']) . 'faces',
            $face_id . Ansel_Faces::getExtension());
    } catch (VFS_Exception $e) {
        Horde::logMessage($e, 'ERR');
        exit;
    }
    header('Content-type: image/' . $GLOBALS['conf']['image']['type']);
    header('X-LIGHTTPD-send-file: ' . $filename);
    header('X-Sendfile: ' . $filename);
    exit;
}

// Run it through PHP
$img = $faces->getFaceImageObject($face_id);
header('Content-type: image/' . $GLOBALS['conf']['image']['type']);
echo $img->raw();
