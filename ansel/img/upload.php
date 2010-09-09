<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

$gallery_id = Horde_Util::getFormData('gallery');
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($gallery_id);
} catch (Ansel_Exception $e) {
    $notification->push(sprintf(_("Gallery %s not found."), $gallery_id), 'horde.error');
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
    exit;
}
if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
    $notification->push(_("You are not authorized to upload photos to this gallery."), 'horde.error');
    Ansel::getUrlFor('view', array('view' => 'List'), true)->redirect();
}

$page = Horde_Util::getFormData('page', 0);
$return_url = Ansel::getUrlFor('view',
                               array('gallery' => $gallery_id,
                                     'slug' => $gallery->get('slug'),
                                     'view' => 'Gallery',
                                     'page' => $page),
                               true);
$view = new Ansel_View_Upload(array('browse_button' => 'pickfiles',
                                    'target' => Horde::selfUrl(),
                                    'drop_target' => 'filelist',
                                    'upload_button' => 'uploadfiles',
                                    'gallery' => $gallery,
                                    'return_target' => $return_url->toString()));
$view->run();
$nojs = $view->handleNoJs();

$title = _("Add Photo");
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
echo '<div class="header" id="galleryHeader"><span class="breadcrumbs">' . Ansel::getBreadCrumbs($gallery) . '</span></div>';
require ANSEL_TEMPLATES . '/image/plupload.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
