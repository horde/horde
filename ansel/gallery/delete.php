<?php
/**
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel');

// Delete/empty the gallery if we're provided with a valid galleryId.
$actionID = Horde_Util::getPost('action');
$galleryId = Horde_Util::getPost('gallery');

if ($galleryId) {
    try {
        $gallery = $ansel_storage->getGallery($galleryId);
    } catch (Ansel_Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
        // Return to the default view.
        header('Location: ' . Ansel::getUrlFor('default_view', array()));
        exit;
    }
    switch ($actionID) {
    case 'delete':
        if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
            $notification->push(sprintf(_("Access denied deleting gallery \"%s\"."),
                                        $gallery->get('name')), 'horde.error');
        } else {
            try {
                $ansel_storage->removeGallery($gallery);
                $notification->push(sprintf(
                    _("Successfully deleted %s."),
                    $gallery->get('name')), 'horde.success');
            } catch (Ansel_Exception $e) {
                $notification->push(sprintf(
                    _("There was a problem deleting %s: %s"),
                    $gallery->get('name'), $e->getMessage()),
                    'horde.error');
            }
        }

        // Clear the OtherGalleries widget cache
        if ($conf['ansel_cache']['usecache']) {
            $injector->getInstance('Horde_Cache')->expire('Ansel_OtherGalleries' . $gallery->get('owner'));
        }

        // Return to the default view.
        header('Location: ' . Ansel::getUrlFor('default_view', array()));
        exit;

    case 'empty':
        if (!$gallery->hasPermission($registry->getAuth(), Horde_Perms::DELETE)) {
            $notification->push(sprintf(_("Access denied deleting gallery \"%s\"."),
                                        $gallery->get('name')),
                                'horde.error');
        } else {
            $ansel_storage->emptyGallery($gallery);
            $notification->push(sprintf(_("Successfully emptied \"%s\""), $gallery->get('name')));
        }
        header('Location: '
               . Ansel::getUrlFor('view',
                                  array('view' => 'Gallery',
                                        'gallery' => $galleryId,
                                        'slug' => $gallery->get('slug')),
                                  true));
        exit;
    }
}
