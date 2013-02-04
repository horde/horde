<?php
/**
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
/**
 * The Ansel_ActionHandler:: class centralizes the handling of various image
 * and gallery actions.
 *
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */

class Ansel_ActionHandler
{
    /**
     * Check for, and handle, common image related actions.
     *
     * @param string $actionID  The action identifier.
     *
     * @return boolean  True if an action was handled, otherwise false.
     * @throws Ansel_Exception
     */
    static function imageActions($actionID)
    {
        global $notification, $registry;

        switch($actionID) {
        case 'delete':
            $image_id = Horde_Util::getFormData('image');
            $gallery_id = Horde_Util::getFormData('gallery');
            if (is_array($image_id)) {
                $images = array_keys($image_id);
            } else {
                $images = array($image_id);
            }
            foreach ($images as $image) {
                $img = $ansel_storage->getImage($image);
                if (empty($gallery_id)) {
                    $gallery_id = $img->gallery;
                }
                $gallery = $GLOBALS['injector']->getInstance('ansel_storage')->getgallery($gallery_id);
                if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                    $notification->push(_("Access denied deleting photos from this gallery."), 'horde.error');
                } else {
                    try {
                        $gallery->removeImage($image);
                        $notification->push(_("Deleted the photo."), 'horde.success');
                    } catch (Ansel_Exception $e) {
                        $notification->push(
                            sprintf(_("There was a problem deleting photos: %s"), $e->getMessage()), 'horde.error');
                    }
                }
            }
            return true;

        case 'move':
            $image_id = Horde_Util::getFormData('image');
            $newGallery = Horde_Util::getFormData('new_gallery');
            $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');
            if (is_array($image_id)) {
                $images = array_keys($image_id);
            } else {
                $images = array($image_id);
            }
            if ($images && $newGallery) {
                try {
                    $newGallery = $ansel_storage->getGallery($newGallery);
                    // Group by gallery first, then process in bulk by gallery.
                    $galleries = array();
                    foreach ($images as $image) {
                        $img = $ansel_storage->getImage($image);
                        $galleries[$img->gallery][] = $image;
                    }
                    foreach ($galleries as $gallery_id => $images) {
                        $gallery = $ansel_storage->getGallery($gallery_id);
                        try {
                            $result = $gallery->moveImagesTo($images, $newGallery);
                            $notification->push(
                                sprintf(ngettext("Moved %d photo from \"%s\" to \"%s\"",
                                                 "Moved %d photos from \"%s\" to \"%s\"",
                                                 count($images)),
                                        count($images), $gallery->get('name'),
                                        $newGallery->get('name')),
                                'horde.success');
                        } catch (Exception $e) {
                            $notification->push($e->getMessage(), 'horde.error');
                        }
                    }
                } catch (Ansel_Exception $e) {
                    $notification->push(_("Bad input."), 'horde.error');
                }
            }
            return true;

        case 'copy':
            $image_id = Horde_Util::getFormData('image');
            $newGallery = Horde_Util::getFormData('new_gallery');
            $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');
            if (is_array($image_id)) {
                $images = array_keys($image_id);
            } else {
                $images = array($image_id);
            }

            if ($images && $newGallery) {
                try {
                    // Group by gallery first, then process in bulk by gallery.
                    $newGallery = $ansel_storage->getGallery($newGallery);
                    $galleries = array();
                    foreach ($images as $image) {
                        $img = $ansel_storage->getImage($image);
                        $galleries[$img->gallery][] = $image;
                    }
                    foreach ($galleries as $gallery_id => $images) {
                        $gallery = $ansel_storage->getGallery($gallery_id);
                        try {
                            $result = $gallery->copyImagesTo($images, $newGallery);
                            $notification->push(
                                sprintf(ngettext("Copied %d photo from %s to %s",
                                                 "Copied %d photos from %s to %s",
                                                 count($images)),
                                        count($images), $gallery->get('name'),
                                        $newGallery->get('name')),
                                'horde.success');
                        } catch (Exception $e) {
                            $notification->push($e->getMessage(), 'horde.error');
                        }
                    }
                } catch (Ansel_Exception $e) {
                    $notification->push(_("Bad input."), 'horde.error');
                }
            }
            return true;

        case 'downloadzip':
            $gallery_id = Horde_Util::getFormData('gallery');
            $image_id = Horde_Util::getFormData('image');
            $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');
            if (!is_array($image_id)) {
                $image_id = array($image_id);
            } else {
                $image_id = array_keys($image_id);
            }

            // All from same gallery.
            if ($gallery_id) {
                $gallery = $ansel_storage->getGallery($gallery_id);
                if (!$registry->getAuth() ||
                    !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) ||
                    $gallery->hasPasswd() || !$gallery->isOldEnough()) {

                    $notification->push(
                        _("Access denied downloading photos from this gallery."),
                        'horde.error');
                    return true;
                }
                $image_ids = $image_id;
            } else {
                $image_ids = array();
                foreach ($image_id as $image) {
                    $img = $ansel_storage->getImage($image);
                    $galleries[$img->gallery][] = $image;
                }
                foreach ($galleries as $gid => $images) {
                    $gallery = $ansel_storage->getGallery($gid);
                    if (!$registry->getAuth() || !$gallery->hasPermission($registry->getAuth(), Horde_Perms::READ) |
                        $gallery->hasPasswd() || !$gallery->isOldEnough()) {

                        continue;
                    }
                    $image_ids = array_merge($image_ids, $images);
                }
            }
            if (count($image_ids)) {
                Ansel::downloadImagesAsZip(null, $image_ids);
            } else {
                $notification->push(_("You must select images to download."), 'horde.error');
            }
            return true;
        }

        return false;
    }

}