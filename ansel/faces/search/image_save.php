<?php
/**
 * Process an single image
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';

/* Check if image exists. */
$tmp = Horde::getTempDir();
$path = $tmp . '/search_face_' . $registry->getAuth() . Ansel_Faces::getExtension();

if (!file_exists($path)) {
    $notification->push(_("You must upload the search photo first"));
    Horde::url('faces/search/image.php')->redirect();
}

$x1 = (int)Horde_Util::getFormData('x1');
$y1 = (int)Horde_Util::getFormData('y1');
$x2 = (int)Horde_Util::getFormData('x2');
$y2 = (int)Horde_Util::getFormData('y2');

if ($x2 - $x1 < 50 || $y2 - $y1 < 50) {
    $notification->push(_("Photo is too small. Search photo must be at least 50x50 pixels."));
    Horde::url('faces/search/image.php')->redirect();
    exit;
}

/* Create Horde_Image driver. */
$img = Ansel::getImageObject();
try {
    $result = $img->loadFile($path);
} catch (Horde_Image_Exception $e) {
    $notification->push($e->getMessage());
    Horde::url('faces/search/image.php')->redirect();
    exit;
}

/* Crop image. */
try {
    $result = $img->crop($x1, $y1, $x2, $y2);
} catch (Horde_Image_Exception $e) {
    $notification->push($e->getMessage());
    Horde::url('faces/search/image.php')->redirect();
    exit;
}

/* Resize image. */
try {
    $img->getDimensions();
    if ($img->_width >= 50) {
        $img->resize(min(50, $img->_width), min(50, $img->_height), true);
    }
} catch (Horde_Image_Exception $e) {
    $notification->push($e->getMessage());
    Horde::url('faces/search/image.php')->redirect();
}

/* Save image. */
$path = $tmp . '/search_face_thumb_' . $registry->getAuth() . Ansel_Faces::getExtension();
if (!file_put_contents($path, $img->raw())) {
    $notification->push(_("Cannot store search photo"));
    Horde::url('faces/search/image.php')->redirect();
    exit;
}

/* Get original signature. */
$signature = $faces->getSignatureFromFile($path);
if (empty($signature)) {
    $notification->push(_("Cannot read photo signature"));
    Horde::url('faces/search/image.php')->redirect();
    exit;
}

/* Save signature. */
$path = $tmp . '/search_face_' . $registry->getAuth() . '.sig';
if (file_put_contents($path, $signature)) {
    Horde::url('faces/search/image.php')->redirect();
    exit;
}

$notification->push(_("Cannot save photo signature"));
Horde::url('faces/search/image.php')->redirect();
exit;
