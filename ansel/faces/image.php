<?php
/**
 * Process an single image (to be called via Ajax)
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$faces = $GLOBALS['injector']->getInstance('Ansel_Faces');

$name = '';
$image_id = (int)Horde_Util::getPost('image');
$reload = (int)Horde_Util::getPost('reload');
$results = $faces->getImageFacesData($image_id);

// Attempt to get faces from the picture if we don't already have results,
// or if we were asked to explicitly try again.
if (($reload || empty($results))) {
    $image = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->getImage($image_id);
    try {
        $image->createView(
            'screen',
            null,
            ($GLOBALS['prefs']->getValue('watermark_auto') ?
                $GLOBALS['prefs']->getValue('watermark_text', '') : '')
            );
        $results = $faces->getFromPicture($image_id, true);
    } catch (Horde_Exception $e) {
        Horde::logMessage($e, 'ERR');
        $results = null;
    }
}

if (!empty($results)) {
    $customurl = Horde::url('faces/custom.php');
    require_once ANSEL_TEMPLATES . '/faces/image.inc';
} else {
    echo _("No faces found");
}
