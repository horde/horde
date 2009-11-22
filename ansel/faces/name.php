<?php
/**
 * Set the name of a single image via Ajax
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
$name = Horde_Util::getFormData('name');

$image = &$ansel_storage->getImage($image_id);
$gallery = &$ansel_storage->getGallery($image->gallery);
if (!$gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
    throw new Horde_Exception('Access denied editing the photo.');
}

$faces = Ansel_Faces::factory();
$result = $faces->setName($face_id, $name);
