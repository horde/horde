<?php
/**
 * Ansel_Gallery_Mode_Normal:: Class for encapsulating gallery methods that
 * depend on the current display mode of the gallery.
 *
 * $Horde: ansel/lib/GalleryMode/Normal.php,v 1.17 2009/07/17 17:26:40 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

class Ansel_GalleryMode_Normal {

    /**
     * @var Ansel_Gallery
     */
    var $_gallery;

    var $_features = array('subgalleries', 'stacks', 'sort_images',
                           'image_captions', 'faces', 'slideshow',
                           'zipdownload', 'upload');

    /**
     * Constructor
     *
     * @param Ansel_Gallery $gallery  The gallery to bind to.
     *
     * @return Ansel_Gallery_ModeNormal
     */
    function Ansel_GalleryMode_Normal($gallery)
    {
        $this->_gallery = $gallery;
    }

    function init()
    {
        // noop
        return true;
    }

    function hasFeature($feature)
    {
        return in_array($feature, $this->_features);
    }

    /**
     * Get the children of this gallery.
     *
     * @param integer $perm  The permissions to limit to.
     * @param integer $from  The child to start at.
     * @param integer $to    The child to end with.
     *
     * @return A mixed array of Ansel_Gallery and Ansel_Image objects that are
     *         children of this gallery.
     */
    function getGalleryChildren($perm = PERMS_SHOW, $from = 0, $to = 0)
    {
        $galleries = array();
        $num_galleries = 0;

        if ($this->hasSubGalleries()) {

            /* Get the number of images and galleries */
            $numimages = $this->countImages();
            $num_galleries = $GLOBALS['ansel_storage']->countGalleries(
                Horde_Auth::getAuth(), PERMS_SHOW, null, $this->_gallery, false);

            /* Now fetch the subgalleries, but only if we need to */
            if ($num_galleries > $from) {
                $galleries = $GLOBALS['ansel_storage']->listGalleries(
                    PERMS_SHOW, null, $this->_gallery, false, $from, $to);
            }
        }

        /* Now grab any images if we still have room */
        if (($to - count($galleries) > 0) || ($from == 0 && $to == 0) &&
             $this->_gallery->data['attribute_images']) {
            $images = $this->getImages(max(0, $from - $num_galleries), $to - count($galleries));
            if (is_a($images, 'PEAR_Error')) {
                Horde::logMessage($images->message, __FILE__, __LINE__,
                                  PEAR_LOG_ERR);
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
    function getGalleryCrumbData()
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

    function setDate($date = array())
    {
        //noop
    }

    function getDate()
    {
        return array();
    }

    /**
     * Return the count this gallery's children
     *
     * @param integer $perm            The permissions to require.
     * @param boolean $galleries_only  Only include galleries, no images.
     *
     * @return integer The count of this gallery's children.
     */
    function countGalleryChildren($perm = PERMS_SHOW, $galleries_only = false)
    {
        if (!$galleries_only && !$this->hasSubGalleries()) {
            return $this->_gallery->data['attribute_images'];
        }

        $gCnt = $GLOBALS['ansel_storage']->countGalleries(Horde_Auth::getAuth(),
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
    function listImages($from = 0, $count = 0)
    {
        return $GLOBALS['ansel_storage']->listImages($this->_gallery->id, $from,
                                                     $count);
    }


    function moveImagesTo($images, $gallery)
    {
        if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            return PEAR::raiseError(sprintf(_("Access denied moving photos to \"%s\"."), $newGallery->get('name')));
        } elseif (!$this->_gallery->hasPermission(Horde_Auth::getAuth(), PERMS_DELETE)) {
            return PEAR::raiseError(sprintf(_("Access denied removing photos from \"%s\"."), $gallery->get('name')));
        }

        /* Sanitize image ids, and see if we're removing our default image. */
        $ids = array();
        foreach ($images as $imageId) {
            $ids[] = (int)$imageId;
            if ($imageId == $this->_gallery->data['attribute_default']) {
                $this->_gallery->set('default', null, true);
            }
        }

        $result = $this->_gallery->_shareOb->_write_db->exec('UPDATE ansel_images SET gallery_id = ' . $gallery->id . ' WHERE image_id IN (' . implode(',', $ids) . ')');
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $this->_gallery->_updateImageCount(count($ids), false);
        $this->_gallery->_updateImageCount(count($ids), true, $gallery->id);

        /* Expire the cache since we have no reason to save() the gallery */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['cache']->expire('Ansel_Gallery' . $gallery->id);
            $GLOBALS['cache']->expire('Ansel_Gallery' . $this->_gallery->id);
        }

        return true;
    }

    function removeImage($image, $isStack)
    {
        /* Make sure $image is an Ansel_Image; if not, try loading it. */
        if (!is_a($image, 'Ansel_Image')) {
            $img = &$this->_gallery->getImage($image);
            if (is_a($img, 'PEAR_Error')) {
                return $img;
            }
            $image = $img;
        } else {
            /* Make sure the image is in this gallery. */
            if ($image->gallery != $this->_gallery->id) {
                return false;
            }
        }

        /* Change gallery info. */
        if ($this->_gallery->data['attribute_default'] == $image->id) {
            $this->_gallery->data['attribute_default'] = null;
            $this->_gallery->data['attribute_default_type'] = 'auto';
        }

        /* Delete cached files from VFS. */
        $image->deleteCache();

        /* Delete original image from VFS. */
        $GLOBALS['ansel_vfs']->deleteFile($image->getVFSPath('full'),
                                          $image->getVFSName('full'));

        /* Delete from SQL. */
        $this->_gallery->_shareOb->_write_db->exec('DELETE FROM ansel_images WHERE image_id = ' . (int)$image->id);

        /* Remove any attributes */
        $this->_gallery->_shareOb->_write_db->exec('DELETE FROM ansel_image_attributes WHERE image_id = ' . (int)$image->id);

        if (!$isStack) {
            $this->_gallery->_updateImageCount(1, false);
        }

        /* Remove any geolocation data */
        $this->_gallery->_shareOb->_write_db->exec('DELETE FROM ansel_images_geolocation WHERE image_id = ' . (int)$image->id);

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
            require_once ANSEL_BASE . '/lib/Faces.php';
            Ansel_Faces::delete($image);
        }

        /* Clear any comments */
        if (($GLOBALS['conf']['comments']['allow'] == 'all' || ($GLOBALS['conf']['comments']['allow'] == 'authenticated' && Horde_Auth::getAuth())) &&
            $GLOBALS['registry']->hasMethod('forums/deleteForum')) {

            $result = $GLOBALS['registry']->call('forums/deleteForum',
                                                 array('ansel', $image->id));

            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __LINE__, __FILE__, PEAR_LOG_ERR);
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
     * @param mixed An array of Ansel_Image objects | PEAR_Error
     */
    function getImages($from = 0, $count = 0)
    {
        $this->_gallery->_shareOb->_db->setLimit($count, $from);

        $images = $this->_gallery->_shareOb->_db->query('SELECT image_id, gallery_id, image_filename, image_type, image_caption, image_uploaded_date, image_sort, image_latitude, image_longitude, image_location, image_geotag_date FROM ansel_images WHERE gallery_id = ' . $this->_gallery->id . ' ORDER BY image_sort');
        if (is_a($images, 'PEAR_Error')) {
            return $images;
        }

        $objects = array();
        while ($image = $images->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset']);
            $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset']);
            $objects[$image['image_id']] = new Ansel_Image($image);
            $GLOBALS['ansel_storage']->images[(int)$image['image_id']] = &$objects[$image['image_id']];
        }
        $images->free();

        $ccounts = $GLOBALS['ansel_storage']->_getImageCommentCounts(array_keys($objects));
        if (!is_a($ccounts, 'PEAR_Error') && count($ccounts)) {
            foreach ($objects as $key => $image) {
                $objects[$key]->commentCount = (!empty($ccounts[$key]) ? $ccounts[$key] : 0);
            }
        }
        return array_values($objects);
    }

    /**
     * Checks if the gallery has any subgallery
     *
     * @return boolean
     */
    function hasSubGalleries()
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
    function countImages($subgalleries = false)
    {
        if ($subgalleries && $this->hasSubGalleries()) {
            $count = $this->countImages(false);
            $galleries = $GLOBALS['ansel_storage']->listGalleries(PERMS_SHOW,
                                                                  false,
                                                                  $this->_gallery,
                                                                  true);

            foreach ($galleries as $galleryId => $gallery) {
                $count += $gallery->countImages();
            }

            return $count;
        }

        return $this->_gallery->data['attribute_images'];
    }


}
