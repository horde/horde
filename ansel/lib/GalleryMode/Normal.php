<?php
/**
 * Ansel_Gallery_Mode_Normal:: Class for encapsulating gallery methods that
 * depend on the current display mode of the gallery.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

class Ansel_GalleryMode_Normal extends Ansel_GalleryMode_Base
{
    /**
     * The array of supported features
     *
     * @var array
     */
    protected $_features = array('subgalleries', 'stacks', 'sort_images',
                                 'image_captions', 'faces', 'slideshow',
                                 'zipdownload', 'upload');

    /**
     * Get the children of this gallery.
     *
     * @param integer $perm   The permissions to limit to.
     * @param integer $from   The child to start at.
     * @param integer $count  The number of children to return.
     *
     * @return array  A mixed array of Ansel_Gallery and Ansel_Image objects
     *                that are children of this gallery.
     */
    public function getGalleryChildren($perm = Horde_Perms::SHOW, $from = 0, $to = 0)
    {
        $galleries = array();
        $num_galleries = 0;
        if ($this->hasSubGalleries()) {
            $storage = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope();
            /* Get the number of images and galleries */
            $numimages = $this->countImages();
            $num_galleries = $storage->countGalleries($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW, null, $this->_gallery, false);

            /* Now fetch the subgalleries, but only if we need to */
            if ($num_galleries > $from) {
                $galleries = $storage->listGalleries(
                        array('parent' => $this->_gallery,
                              'allLevels' => false,
                              'from' => $from,
                              'count' => $to));
            }
        }

        /* Now grab any images if we still have room */
        if (($to - count($galleries) > 0) || ($from == 0 && $to == 0) &&
             $this->_gallery->data['attribute_images']) {

            try {
                $images = $this->getImages(max(0, $from - $num_galleries), $to - count($galleries));
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                $images = array();
            }
        } else {
            $images = array();
        }

        return array_merge($galleries, $images);
    }

    /**
     * Get an array describing where this gallery is in a breadcrumb trail.
     *
     * @return  An array of 'title' and 'navdata' hashes with the [0] element
     *          being the deepest part.
     */
    public function getGalleryCrumbData()
    {
        $trail = array();
        $text = htmlspecialchars($this->_gallery->get('name'));
        $navdata = array('view' => 'Gallery',
                         'gallery' => $this->_gallery->id,
                         'slug' => $this->_gallery->get('slug'));
        $trail[] = array('title' => $text, 'navdata' => $navdata);
        $parent_list = array_reverse($this->_gallery->getParents());
        foreach ($parent_list as $p) {
            $text = htmlspecialchars($p->get('name'));
            $navdata = array('view' => 'Gallery',
                             'gallery' => $p->id,
                             'slug' => $p->get('slug'));
            $trail[] = array('title' => $text, 'navdata' => $navdata);
        }

        return $trail;
    }

    /**
     * Return the count this gallery's children
     *
     * @param integer $perm            The permissions to require.
     * @param boolean $galleries_only  Only include galleries, no images.
     *
     * @return integer The count of this gallery's children.
     */
    public function countGalleryChildren($perm = Horde_Perms::SHOW, $galleries_only = false)
    {
        if (!$galleries_only && !$this->hasSubGalleries()) {
            return $this->_gallery->data['attribute_images'];
        }

        $gCnt = $GLOBALS['injector']->getInstance('Ansel_Storage')
                ->getScope()
                ->countGalleries($GLOBALS['registry']->getAuth(),
                                 $perm, null,
                                 $this->_gallery, false);

        if (!$galleries_only) {
            $iCnt = $this->countImages(false);
        } else {
            $iCnt = 0;
        }

        return $gCnt + $iCnt;
    }

    /**
     * Lists a slice of the image ids in this gallery.
     *
     * @param integer $from  The image to start listing.
     * @param integer $count The numer of images to list.
     *
     * @return mixed  An array of image_ids | PEAR_Error
     */
    public function listImages($from = 0, $count = 0)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Storage')
            ->getScope()
            ->listImages($this->_gallery->id, $from, $count);
    }

    /**
     * Move images from this gallery to another.
     *
     * @param array $images           The image ids to move.
     * @param Ansel_Gallery $gallery  The gallery to move images into.
     *
     * @return boolean
     * @throws Ansel_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function moveImagesTo($images, $gallery)
    {
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
          throw new Horde_Exception_PermissionDenied(sprintf(_("Access denied moving photos to \"%s\"."), $newGallery->get('name')));
        } elseif (!$this->_gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
            throw new Horde_Exception_PermissionDenied(sprintf(_("Access denied removing photos from \"%s\"."), $gallery->get('name')));
        }

        /* Sanitize image ids, and see if we're removing our key image. */
        $ids = array();
        foreach ($images as $imageId) {
            $ids[] = (int)$imageId;
            if ($imageId == $this->_gallery->data['attribute_default']) {
                $this->_gallery->set('default', null, true);
            }
        }

        $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->setImagesGallery($ids, $gallery->id);
        $this->_gallery->updateImageCount(count($ids), false);
        $gallery->updateImageCount(count($ids), true);

        /* Expire the cache since we have no reason to save() the gallery */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $gallery->id);
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $this->_gallery->id);
        }

        return true;
    }

    /**
     * Remove an image from Ansel.
     *
     * @param integer | Ansel_Image $image  The image id or object
     * @param boolean $isStack              This represents a stack image
     *
     * @return boolean
     * @throws Horde_Exception_NotFound
     */
    public function removeImage($image, $isStack)
    {
        /* Make sure $image is an Ansel_Image; if not, try loading it. */
        if (!($image instanceof Ansel_Image)) {
            $image = $this->_gallery->getImage($image);
        } else {
            /* Make sure the image is in this gallery. */
            if ($image->gallery != $this->_gallery->id) {
                throw new Horde_Exception_NotFound(_("Image not found in gallery."));
            }
        }

        /* Was this image the gallery's key image? */
        if ($this->_gallery->data['attribute_default'] == $image->id) {
            $this->_gallery->data['attribute_default'] = null;
            $this->_gallery->data['attribute_default_type'] = 'auto';
        }

        /* Delete cached files from VFS. */
        $image->deleteCache();

        /* Delete original image from VFS. */
        try {
            $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->deleteFile($image->getVFSPath('full'), $image->getVFSName('full'));
        } catch (VFS_Exception $e) {}

        /* Delete from storage */
        $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->deleteImage($image->id);
        if (!$isStack) {
            $this->_gallery->updateImageCount(1, false);
        }

        /* Update the modified flag if we are not a stack image */
        if (!$isStack) {
            $this->_gallery->data['attribute_last_modified'] = time();
        }

        /* Save all gallery changes */
        $this->_gallery->save();

        /* Clear the image's tags */
        $image->setTags(array());

        /* Clear the image's faces */
        if ($image->facesCount) {
            Ansel_Faces::delete($image);
        }

        /* Clear any comments */
        if (($GLOBALS['conf']['comments']['allow'] == 'all' || ($GLOBALS['conf']['comments']['allow'] == 'authenticated' && $GLOBALS['registry']->getAuth())) &&
            $GLOBALS['registry']->hasMethod('forums/deleteForum')) {

            $result = $GLOBALS['registry']->call('forums/deleteForum',
                                                 array('ansel', $image->id));

            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return false;
            }
        }

        return true;
    }

    /**
     * Gets a slice of the images in this gallery.
     *
     * @param integer $from  The image to start fetching.
     * @param integer $count The numer of images to return.
     *
     * @param array An array of Ansel_Image objects
     */
    public function getImages($from = 0, $count = 0)
    {
        $images = $GLOBALS['injector']->getInstance('Ansel_Storage')
            ->getScope()
            ->getImages(array('gallery_id' => $this->_gallery->id,
                              'count' => $count,
                              'from' => $from));

        return array_values($images);
    }

    /**
     * Checks if the gallery has any subgallery
     *
     * @return boolean
     */
    public function hasSubGalleries()
    {
        return $this->_gallery->get('has_subgalleries') == 1;
    }

    /**
     * Returns the number of images in this gallery and, optionally, all
     * sub-galleries.
     *
     * @param boolean $subgalleries  Determines whether subgalleries should
     *                               be counted or not.
     *
     * @return integer number of images in this gallery
     */
    public function countImages($subgalleries = false)
    {
        if ($subgalleries && $this->hasSubGalleries()) {
            $count = $this->countImages(false);
            $galleries = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getScope()
                ->listGalleries(array('parent' => $this->_gallery));

            foreach ($galleries as $galleryId => $gallery) {
                $count += $gallery->countImages();
            }

            return $count;
        }

        return $this->_gallery->data['attribute_images'];
    }

}
