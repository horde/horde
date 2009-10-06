<?php
/**
 * Explicitly add/edit a face range to an image.
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
$page = Horde_Util::getFormData('page', 0);
$url = Horde_Util::getFormData('url');
$urlparams = array('page' => $page);
if (!empty($url)) {
    $urlparams['url'] = $url;
}
$form_post = Horde_Util::addParameter(Horde::applicationUrl('faces/savecustom.php'), $urlparams);

$image = &$ansel_storage->getImage($image_id);
if (is_a($image, 'PEAR_Error')) {
    $notification->push($image);
    header('Location: ' . Horde::applicationUrl('list.php'));
    exit;
}

$gallery = $ansel_storage->getGallery($image->gallery);
if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
    $notification->push(_("Access denied editing the photo."));
    header('Location: ' . Ansel::getUrlFor('view', array('gallery' => $image->gallery)));
    exit;
}

$x1 = 0;
$y1 = 0;
$x2 = $conf['screen']['width'];
$y2 = $conf['screen']['width'];
$name = Horde_Util::getFormData('name');

if ($face_id) {
    $faces = Ansel_Faces::factory();
    try {
        $face = $faces->getFaceById($face_id, true);
    } catch (Horde_Exception $e) {
        $notification->push($e->getMessage());
    }

    $x1 = $face['face_x1'];
    $y1 = $face['face_y1'];
    $x2 = $face['face_x2'];
    $y2 = $face['face_y2'];
    if (!empty($face['face_name'])) {
        $name = $face['face_name'];
    }

}

$height = $x2 - $x1;
$width = $y2 - $y1;

$title = _("Create a new face");

Horde::addScriptFile('builder.js');
Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('controls.js', 'horde');
Horde::addScriptFile('dragdrop.js', 'horde');
Horde::addScriptFile('cropper.js');
Horde::addScriptFile('stripe.js', 'horde');

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/faces/custom.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
