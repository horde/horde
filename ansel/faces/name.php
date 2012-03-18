<?php
/**
 * Set the name of a single image via Ajax
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

$image_id = (int)Horde_Util::getFormData('image');
$face_id = (int)Horde_Util::getFormData('face');
$name = Horde_Util::getFormData('name');

$image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($image_id);
$gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')>getGallery($image->gallery);
if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
    throw new Horde_Exception('Access denied editing the photo.');
}

$faces = $injector->getInstance('Ansel_Faces');
$result = $faces->setName($face_id, $name);
