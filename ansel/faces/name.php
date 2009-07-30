<?php
/**
 * Set the name of a single image via Ajax
 *
 * $Horde: ansel/faces/name.php,v 1.9 2009/07/08 18:28:41 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../lib/base.php';
require_once ANSEL_BASE . '/lib/Faces.php';

$image_id = (int)Horde_Util::getFormData('image');
$face_id = (int)Horde_Util::getFormData('face');
$name = Horde_Util::getFormData('name');

$image = &$ansel_storage->getImage($image_id);
if (is_a($image, 'PEAR_Error')) {
    die($image->getMessage());
}

$gallery = &$ansel_storage->getGallery($image->gallery);
if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
    die(_("Access denied editing the photo."));
}

$faces = Ansel_Faces::factory();
if (is_a($faces, 'PEAR_Error')) {
    die($faces->getMessage());
}

$result = $faces->setName($face_id, $name);
if (is_a($result, 'PEAR_Error')) {
    die($result->getDebugInfo());
}