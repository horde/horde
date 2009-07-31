<?php
/**
 * Ansel_Gallery_Mode_Normal:: Class for encapsulating gallery methods that
 * depend on the current display mode of the gallery.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Ansel_GalleryMode_RemoteVimeo {

    var $_vimeo;
    var $_thumbs;

    /**
     * @var Ansel_Gallery
     */
    var $_gallery;
    var $_features = array();

    /**
     * Constructor
     *
     * @param Ansel_Gallery $gallery  The gallery to bind to.
     *
     * @return Ansel_Gallery_ModeNormal
     */
    function Ansel_GalleryMode_RemoteVimeo($gallery)
    {
        // Build a Horde_Service_Vimeo object
        // It *requires* a http client object and can make use of a cache object,
        $params = array('http_client' => new Horde_Http_Client(),
                        'cache' => $GLOBALS['cache'],
                        'cache_lifetime' => $GLOBALS['conf']['cache']['default_lifetime']);

        $this->_vimeo = Horde_Service_Vimeo::factory('Simple', $params);
        $vimeo_id = 'user1015172'; //TODO: Get this from prefs?
        $this->_thumbs = unserialize($this->_vimeo->user($vimeo_id)->clips()->run());
        $this->_gallery = $gallery;
    }

    function init()
    {
        $remote_ids = array();

        // Get the remote video_ids
        foreach ($this->_thumbs as $thumb) {
            $remote_ids[$thumb['clip_id']] = false;
        }

        // Get localimage objects...
        $images = $this->getImages();
        if (!is_a($images, 'PEAR_Error')) {
            foreach ($images as $image) {
                $caption = $image->caption;
                if (in_array($caption, array_keys($remote_ids))) {
                    // We still have this video on Vimeo.
                    // AND we know that we will be checking for this locally
                    // later, so save the info now.
                    $remote_ids[$caption] = true;
                } else {
                    // Remote no longer exists - delete the local thumbnail
                    $this->removeImage($image, false);
                }
            }

            // Now check the other direction
            foreach($this->_thumbs as $thumb) {
                if (!$remote_ids[$thumb['clip_id']]) {
                    // We didn't find a match in any of our local images earlier
                    // create one now.
                    $hc = new Horde_Http_Client();
                    $response = $hc->get($thumb['thumbnail_large']);

                    $image_id = $this->_gallery->addImage(array(
                        'image_filename' => $thumb['title'],
                        'image_caption' => $thumb['clip_id'],
                        'data' => $response->getBody(),
                        'image_type' => $response->getHeader('Content-Type')
                        ));
                }
            }
        }
    }

    function hasFeature($feature)
    {
        return in_array($feature, $this->_features);
    }

    /**
     * Get the children of this gallery.
     *
     * Should never be called with a RemoteVimeo gallery since we override
     * fetchChildren() in Ansel_GalleryRenderer...but implement something
     * sensible just in case that ever changes.
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
        if ($this->_gallery->data['attribute_images']) {
            $images = $this->getImages($from, $to);
            if (is_a($images, 'PEAR_Error')) {
                Horde::logMessage($images->message, __FILE__, __LINE__,
                                  PEAR_LOG_ERR);
                $images = array();
            }
        } else {
            $images = array();
        }

        return $images;
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
        return count($this->_thumbs);
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
        return false;
    }

    function removeImage($image, $isStack)
    {
        return false;
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
        $images = $this->_gallery->_shareOb->_db->query('SELECT image_id, gallery_id, image_filename, image_type, image_caption, image_uploaded_date, image_sort FROM ansel_images WHERE gallery_id = ' . $this->_gallery->id . ' ORDER BY image_sort');
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
        return false;
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
        return count($this->_thumbs);
    }

}
