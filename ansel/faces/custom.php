<?php
/**
 * Explicitly add/edit a face range to an image.
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
$page = Horde_Util::getFormData('page', 0);
$url = Horde_Util::getFormData('url');
$urlparams = array('page' => $page);
if (!empty($url)) {
    $urlparams['url'] = $url;
}
$form_post = Horde::url('faces/savecustom.php')->add($urlparams);

try {
    $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($image_id);
} catch (Ansel_Exception $e) {
    $notification->push($image);
    Horde::url('list.php')->redirect();
    exit;
}

$gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($image->gallery);
if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
    $notification->push(_("Access denied editing the photo."));
    Ansel::getUrlFor('view', array('gallery' => $image->gallery))->redirect();
    exit;
}

$x1 = 0;
$y1 = 0;
$x2 = $conf['screen']['width'];
$y2 = $conf['screen']['width'];
$name = Horde_Util::getFormData('name');

if ($face_id) {
    $faces = $injector->getInstance('Ansel_Faces');
    try {
        $face = $faces->getFaceById($face_id, true);
        $x1 = $face['face_x1'];
        $y1 = $face['face_y1'];
        $x2 = $face['face_x2'];
        $y2 = $face['face_y2'];
        if (!empty($face['face_name'])) {
            $name = $face['face_name'];
        }
    } catch (Horde_Exception $e) {
        $notification->push($e->getMessage());
        Horde::url('list.php')->redirect();
    }
}

$height = $x2 - $x1;
$width = $y2 - $y1;

$page_output->addScriptFile('scriptaculous/builder.js', 'horde');
$page_output->addScriptFile('scriptaculous/effects.js', 'horde');
$page_output->addScriptFile('scriptaculous/controls.js', 'horde');
$page_output->addScriptFile('scriptaculous/dragdrop.js', 'horde');
$page_output->addScriptFile('cropper.js');
$page_output->addScriptFile('stripe.js', 'horde');

$page_output->addThemeStylesheet('cropper.css');

$page_output->header(array(
    'title' => _("Create a new face")
));
$notification->notify(array('listeners' => 'status'));
require ANSEL_TEMPLATES . '/faces/custom.inc';
$page_output->footer();
