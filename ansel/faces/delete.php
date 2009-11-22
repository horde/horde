<?php
/**
 * Delete a face from an image.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../lib/base.php';

$image_id = (int)Horde_Util::getFormData('image');
$face_id = (int)Horde_Util::getFormData('face');

$image = &$ansel_storage->getImage($image_id);
if (is_a($image, 'PEAR_Error')) {
    die($image->getMessage());
}

$gallery = &$ansel_storage->getGallery($image->gallery);
if (!$gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
    die(_("Access denied editing the photo."));
}

$faces = Ansel_Faces::factory();
$result = $faces->delete($image, $face_id);

