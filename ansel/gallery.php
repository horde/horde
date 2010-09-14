<?php
/**
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

// Redirect to the gallery list if no action has been requested.
$actionID = Horde_Util::getFormData('actionID');
if (is_null($actionID)) {
    Horde::url('view.php?view=List', true)->redirect();
    exit;
}

switch ($actionID) {
case 'add':
case 'addchild':
case 'save':
case 'modify':
    $view = new Ansel_View_GalleryProperties(array('actionID' => $actionID,
                                                   'url' => new Horde_Url(Horde_Util::getFormData('url')),
                                                   'gallery' => Horde_Util::getFormData('gallery')));
    $view->run();
    exit;

case 'downloadzip':
    $galleryId = Horde_Util::getFormData('gallery');
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
    if (!$registry->getAuth() ||
        !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ)) {

        $notification->push(sprintf(_("Access denied downloading photos from \"%s\"."), $gallery->get('name')), 'horde.error');
        Horde::url('view.php?view=List', true)->redirect();
        exit;
    }

    Ansel::downloadImagesAsZip($gallery);
    exit;

case 'delete':
case 'empty':
    // Print the confirmation screen.
    $galleryId = Horde_Util::getFormData('gallery');
    if ($galleryId) {
        try {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
            require ANSEL_TEMPLATES . '/common-header.inc';
            echo Horde::menu();
            $notification->notify(array('listeners' => 'status'));
            require ANSEL_TEMPLATES . '/gallery/delete_confirmation.inc';
            require $registry->get('templates', 'horde') . '/common-footer.inc';
            exit;
        } catch (Ansel_Exception $e) {
            $notification->push($gallery->getMessage(), 'horde.error');
        }
    }

    // Return to the gallery list.
    Horde::url(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
    exit;

case 'generateDefault':
    // Re-generate the default pretty gallery image.
    $galleryId = Horde_Util::getFormData('gallery');
    try {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
        $gallery->clearStacks();
        $notification->push(_("The gallery's default photo has successfully been reset."), 'horde.success');
        Horde::url('view.php', true)->add('gallery', $galleryId)->redirect();
        exit;
    } catch (Ansel_Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
        Horde::url('index.php', true)->redirect();
        exit;
    }

case 'generateThumbs':
    // Re-generate all of this gallery's prettythumbs.
    $galleryId = Horde_Util::getFormData('gallery');
    try {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
    } catch (Ansel_Exception $e) {
        $notification->push($gallery->getMessage(), 'horde.error');
        Horde::url('index.php', true)->redirect();
        exit;
    }
    $gallery->clearThumbs();
    $notification->push(_("The gallery's thumbnails have successfully been reset."), 'horde.success');
    Horde::url('view.php', true)->add('gallery', $galleryId)->redirect();
    exit;

case 'deleteCache':
    // Delete all cached image views.
    $galleryId = Horde_Util::getFormData('gallery');
    try {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($galleryId);
    } catch (Ansel_Exception $e) {
        $notification->push($gallery->getMessage(), 'horde.error');
        Horde::url('index.php', true)->redirect();
        exit;
    }
    $gallery->clearViews();
    $notification->push(_("The gallery's views have successfully been reset."), 'horde.success');
    Horde::url('view.php', true)->add('gallery', $galleryId)->redirect();
    exit;

default:
    Horde::url(Ansel::getUrlFor('view', array('view' => 'List'), true))->redirect();
    exit;
}

