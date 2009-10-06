<?php
/**
 * Find faces and display faces UI for entire gallery.
 *
 * TODO: Turn this into an Ansel_View::
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../lib/base.php';

$gallery_id = (int)Horde_Util::getFormData('gallery');
if (empty($gallery_id)) {
    $notification->push(_("No gallery specified"), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('default_view', array()));
    exit;
}
$gallery = $ansel_storage->getGallery($gallery_id);
if (is_a($gallery, 'PEAR_Error')) {
    $notification->push($gallery->getMessage(), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('gallery' => $gallery_id)));
    exit;
} elseif (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
    $notification->push(sprintf(_("Access denied editing gallery \"%s\"."), $gallery->get('name')), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('gallery' => $gallery_id)));
    exit;
}
$gallery->setDate(Ansel::getDateParameter());
$page = Horde_Util::getFormData('page', 0);
$perpage = min($prefs->getValue('tilesperpage'), $conf['thumbnail']['perpage']);
$images = $gallery->getImages($page * $perpage, $perpage);

$reloadimage = $registry->getImageDir('horde') . '/reload.png';
$customimage = $registry->getImageDir('horde') . '/layout.png';
$customurl = Horde_Util::addParameter(Horde::applicationUrl('faces/custom.php'), 'page', $page);
$face = Ansel_Faces::factory();
$autogenerate = $face->canAutogenerate();
$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_UI_Pager(
    'page', $vars,
    array('num' => $gallery->countImages(),
          'url' => 'faces/gallery.php',
          'perpage' => $perpage));
$pager->preserve('gallery',  $gallery_id);

$title = sprintf(_("Searching for faces in %s"), Horde::link(Ansel::getUrlFor('view', array('gallery' => $gallery_id, 'view' => 'Gallery'))) . $gallery->get('name') . '</a>');
Horde::addScriptFile('stripe.js', 'horde');
Horde::addScriptFile('popup.js', 'horde');
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/faces/gallery.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
