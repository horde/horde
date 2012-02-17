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

require_once dirname(__FILE__) . '/../lib/Application.php';
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

$title = _("Create a new face");

Horde::addScriptFile('builder.js', 'horde');
Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('controls.js', 'horde');
Horde::addScriptFile('dragdrop.js', 'horde');
Horde::addScriptFile('cropper.js');
Horde::addScriptFile('stripe.js', 'horde');

$injector->getInstance('Horde_Themes_Css')->addThemeStylesheet('cropper.css');

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require ANSEL_TEMPLATES . '/faces/custom.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
