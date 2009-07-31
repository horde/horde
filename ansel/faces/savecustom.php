<?php
/**
 * Process an single image (to be called by ajax)
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../lib/base.php';

$image_id = (int)Horde_Util::getFormData('image_id');
$gallery_id = (int)Horde_Util::getFormData('gallery_id');
$face_id = (int)Horde_Util::getFormData('face_id');
$url = Horde_Util::getFormData('url');
$page = Horde_Util::getFormData('page', 0);

$back_url = empty($url) ?
    Horde_Util::addParameter(Horde::applicationUrl('faces/gallery.php'),
                             array('gallery' => $gallery_id,
                                   'page' => $page), null, false) :
    $url;

if (Horde_Util::getPost('submit') == _("Cancel")) {
    $notification->push(_("Changes cancelled."), 'horde.warning');
    header('Location: ' . $back_url);
    exit;
}
try {
    $faces = Ansel_Faces::factory();
    $result = $faces->saveCustomFace(
                           $face_id,
                           $image_id,
                           (int)Horde_Util::getFormData('x1'),
                           (int)Horde_Util::getFormData('y1'),
                           (int)Horde_Util::getFormData('x2'),
                           (int)Horde_Util::getFormData('y2'),
                           Horde_Util::getFormData('name'));
} catch (Horde_Exception $e) {
    $notification->push($e->getMessage());
    header('Location: ' . $back_url);
    exit;
}

if ($face_id == 0) {
    $notification->push(_("Face successfuly created"), 'horde.success');
} else {
    $notification->push(_("Face successfuly updated"), 'horde.success');
}

header('Location: ' . $back_url);
exit;
