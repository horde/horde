<?php
/**
 * Process an single image (to be called via Ajax)
 *
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
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
    $image = $storage->getImage($image_id);
    try {
        $image->createView(
            'screen',
            null,
            ($prefs->getValue('watermark_auto') ?
                $prefs->getValue('watermark_text', '') : '')
            );
        $results = $faces->getFromPicture($image_id, true);
    } catch (Ansel_Exception $e) {
        Horde::log($e, 'ERR');
        $results = null;
    }
}

if (!empty($results)) {
    $customurl = Horde::url('faces/custom.php');
    require_once ANSEL_TEMPLATES . '/faces/image.inc';
} else {
    echo _("No faces found");
}
