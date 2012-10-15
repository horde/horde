<?php
/**
 * Find faces and display faces UI for entire gallery.
 *
 * TODO: Turn this into an Ansel_View::
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

$gallery_id = (int)Horde_Util::getFormData('gallery');
if (empty($gallery_id)) {
    $notification->push(_("No gallery specified"), 'horde.error');
    Ansel::getUrlFor('default_view', array())->redirect();
    exit;
}
try {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($gallery_id);
} catch (Ansel_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    Ansel::getUrlFor('view', array('gallery' => $gallery_id))->redirect();
    exit;
}
if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
    $notification->push(sprintf(_("Access denied editing gallery \"%s\"."), $gallery->get('name')), 'horde.error');
    Ansel::getUrlFor('view', array('gallery' => $gallery_id))->redirect();
    exit;
}
$gallery->setDate(Ansel::getDateParameter());
$page = Horde_Util::getFormData('page', 0);
$perpage = min($prefs->getValue('tilesperpage'), $conf['thumbnail']['perpage']);
$images = $gallery->getImages($page * $perpage, $perpage);

$reloadimage = Horde::img('reload.png');
$customimage = Horde::img('layout.png');
$customurl = Horde::url('faces/custom.php')->add('page', $page);
$face = $injector->getInstance('Ansel_Faces');
$autogenerate = $face->canAutogenerate();

$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager(
    'page',
    $vars,
    array(
        'num' => $gallery->countImages(),
        'url' => 'faces/gallery.php',
        'perpage' => $perpage
    )
);
$pager->preserve('gallery',  $gallery_id);

$title = sprintf(_("Searching for faces in %s"),Ansel::getUrlFor('view', array('gallery' => $gallery_id, 'view' => 'Gallery'))->link() . $gallery->get('name') . '</a>');
$page_output->addScriptFile('stripe.js', 'horde');
$page_output->addScriptFile('popup.js', 'horde');

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
require ANSEL_TEMPLATES . '/faces/gallery.inc';
$page_output->footer();
