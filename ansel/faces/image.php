<?php
/**
 * Process an single image (to be called via Ajax)
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../lib/base.php';

$faces = Ansel_Faces::factory();

$name = '';
$autocreate = true;
$image_id = (int)Horde_Util::getFormData('image');
$reload = (int)Horde_Util::getFormData('reload');
$result = $faces->getImageFacesData($image_id);

// Attempt to get faces from the picture if we don't already have results,
// or if we were asked to explicitly try again.
if (($reload || empty($result))) {
    $image = &$ansel_storage->getImage($image_id);
    try {
        $image->createView('screen');
        $result = $faces->getFromPicture($image_id, $autocreate);
    } catch (Horde_Exception $e) {
        Horde::logMessage($e->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
        $result = null;
    }
}

if (!empty($result)) {
    $imgdir = $registry->getImageDir('horde');
    $customurl = Horde::applicationUrl('faces/custom.php');
    require_once ANSEL_TEMPLATES . '/faces/image.inc';
} else {
    echo _("No faces found");
}