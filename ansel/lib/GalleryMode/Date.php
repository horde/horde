<?php
/**
 * Ansel_GalleryMode_Date:: Class for encapsulating gallery methods that
 * depend on the current display mode of the gallery being Date.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_GalleryMode_Date
{
    /**
     * @var Ansel_Gallery
     */
    var $_gallery;

    /**
     * The date part array for the current grouping.
     *
     * @var array
     */
    var $_date = array();

    var $_features = array('slideshow', 'zipdownload', 'upload');

    var $_subGalleries = null;

    /**
     * Constructor
     *
     * @param Ansel_Gallery $gallery  The gallery to bind to.
     *
     * @return Ansel_Gallery_ModeDate
     */
    function Ansel_GalleryMode_Date($gallery)
    {
        $this->_gallery = $gallery;
    }

    function init() {
        // noop
        return true;
    }

    function hasFeature($feature)
    {
        /* First, some special cases */
        switch ($feature) {
        case 'sort_images':
        case 'image_captions':
        case 'faces':
            /* Only allowed when we are on a specific day */
            if (!empty($this->_date['day'])) {
                return true;
            }
            break;
        }
        return in_array($feature, $this->_features);
    }

    /**
     * Get an array describing where this gallery is in a breadcrumb trail.
     *
     * @return  An array of 'title' and 'navdata' hashes with the [0] element
     *          being the deepest part.
     */
    function getGalleryCrumbData()
    {
        // Convienience
        $year = !empty($this->_date['year']) ? $this->_date['year'] : 0;
        $month = !empty($this->_date['month']) ? $this->_date['month'] : 0;
        $day = !empty($this->_date['day']) ? $this->_date['day'] : 0;
        $trail = array();


        // Do we have any date parts?
        if (!empty($year)) {
            if (!empty($day)) {
                $date = new Horde_Date($this->_date);
                $text = $date->format('jS');

                $navdata =  array('view' => 'Gallery',
                                  'gallery' => $this->_gallery->id,
                                  'slug' => $this->_gallery->get('slug'),
                                  'year' => $year,
                                  'month' => $month,
                                  'day' => $day);

                $trail[] = array('title' => $text, 'navdata' => $navdata);

            }

            if (!empty($month)) {
                $date = new Horde_Date(array('year' => $year,
                                             'month' => $month,
                                             'day' => 1));
                $text = $date->format('F');
                $navdata = array('view' => 'Gallery',
                                 'gallery' => $this->_gallery->id,
                                 'slug' => $this->_gallery->get('slug'),
                                 'year' => $year,
                                 'month' => $month);
                $trail[] = array('title' => $text, 'navdata' => $navdata);
            }

            $navdata = array('view' => 'Gallery',
                             'gallery' => $this->_gallery->id,
                             'slug' => $this->_gallery->get('slug'),
                             'year' => $year);
            $trail[] = array('title' => $year, 'navdata' => $navdata);

        } else {

            // This is the first level of a date mode gallery.
            $navdata = array('view' => 'Gallery',
                             'gallery' => $this->_gallery->id,
                             'slug' => $this->_gallery->get('slug'));
            $trail[] = array('title' => _("All dates"), 'navdata' => $navdata);
        }

        $text = htmlspecialchars($this->_gallery->get('name'), ENT_COMPAT, $GLOBALS['registry']->getCharset());
        $navdata = array('view' => 'Gallery',
                         'gallery' => $this->_gallery->id,
                         'slug' => $this->_gallery->get('slug'));

        $trail[] = array('title' => $text, 'navdata' => $navdata);

        return $trail;
    }

    /**
     * Getter for _date
     *
     * @return array  A date parts array.
     */
    function getDate()
    {
        return $this->_date;
    }

    /**
     * Setter for _date
     *
     * @param array $date
     */
    function setDate($date = array())
    {
        $this->_date = $date;
    }

    function _getSubGalleries()
    {
        if (!is_array($this->_subGalleries)) {
            /* Get a list of all the subgalleries */
            $subs = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getScope()
                ->listGalleries(array('parent' => $this->_gallery));
            $this->_subGalleries = array_keys($subs);
        }
    }

    /**
     * Get the children of this gallery.
     *
     * @param integer $perm  The permissions to limit to.
     * @param integer $from  The child to start at.
     * @param integer $to    The child to end with.
     *
     * @return A mixed array of Ansel_Gallery_Date and Ansel_Image objects.
     */
    function getGalleryChildren($perm = Horde_Perms::SHOW, $from = 0, $to = 0, $noauto = false)
    {
        global $ansel_db;

        /* Cache the results */
        static $children = array();

        /* Ansel Storage */
        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope();

        $cache_key = md5($this->_gallery->id . serialize($this->_date) . $from . $to);
        if (!empty($children[$cache_key])) {
            return $children[$cache_key];
        }

        /* Get a list of all the subgalleries */
        $this->_getSubGalleries();
        if (count($this->_subGalleries)) {
            $gallery_where = 'gallery_id IN (' . implode(', ', $this->_subGalleries) . ', ' . $this->_gallery->id . ')';
        } else {
            $gallery_where = 'gallery_id = ' . $this->_gallery->id;
        }

        $sorted_dates = array();
        /* First let's see how specific the date is */
        if (!count($this->_date) || empty($this->_date['year'])) {
            /* All available images - grouped by year */
            $images = $ansel_storage->listImages($this->_gallery->id, 0, 0, array('image_id', 'image_original_date'), $gallery_where);
            $dates = array();
            foreach ($images as $key => $image) {
                $dates[date('Y', $image['image_original_date'])][] = $key;
            }
            $keys = array_keys($dates);

            /* Drill down further if we only have a single group */
            if (!$noauto && count($keys) == 1) {
                $this->_date['year'] = array_pop($keys);
                return $this->getGalleryChildren($perm, $from, $to, $noauto);
            }
            sort($keys, SORT_NUMERIC);
            foreach ($keys as $key) {
                $sorted_dates[$key] = $dates[$key];
            }
            $display_unit = 'year';
        } elseif (empty($this->_date['month'])) {
            /* Specific year - grouped by month */
            $start = new Horde_Date(
                array('year' => $this->_date['year'],
                      'month' => 1,
                      'day' => 1));

            /* Last second of the year */
            $end = new Horde_Date($start);
            $end->mday = 31;
            $end->month = 12;
            $end->hour = 23;
            $end->min = 59;
            $end->sec = 59;

            /* Get the image ids and dates */
            $where = 'image_original_date <= ' . (int)$end->timestamp() . ' AND image_original_date >= ' . (int)$start->timestamp();
            if (!empty($gallery_where)) {
                $where .= ' AND ' . $gallery_where;
            }
            $images= $ansel_storage->listImages($this->_gallery->id, 0, 0, array('image_id', 'image_original_date'), $where);
            $dates = array();
            foreach ($images as $key => $image) {
                $dates[date('n', $image['image_original_date'])][] = $key;
            }
            $keys = array_keys($dates);

            /* Only 1 date grouping here, automatically drill down */
            if (!$noauto && count($keys) == 1) {
                $this->_date['month'] = array_pop($keys);
                return $this->getGalleryChildren($perm, $from, $to, $noauto);
            }
            sort($keys, SORT_NUMERIC);
            foreach ($keys as $key) {
                $sorted_dates[$key] = $dates[$key];
            }
            $display_unit = 'month';
        } elseif (empty($this->_date['day'])) {
            /* A single month - group by day */
            $start = new Horde_Date(
                array('year' => $this->_date['year'],
                      'month' => $this->_date['month'],
                      'day' => 1));

            /* Last second of the month */
            $end = new Horde_Date($start);
            $end->mday = Horde_Date_Utils::daysInMonth($end->month, $end->year);
            $end->hour = 23;
            $end->min = 59;
            $end->sec = 59;

            $where = 'image_original_date <= ' . (int)$end->timestamp() . ' AND image_original_date >= ' . (int)$start->timestamp();
            if (!empty($gallery_where)) {
                $where .= ' AND ' . $gallery_where;
            }
            $images= $ansel_storage->listImages($this->_gallery->id, 0, 0, array('image_id', 'image_original_date'), $where);
            $dates = array();
            foreach ($images as $key => $image) {
                $dates[date('d', $image['image_original_date'])][] = $key;
            }
            $keys = array_keys($dates);

            /* Only a single grouping, go deeper */
            if (!$noauto && count($keys) == 1) {
                $this->_date['day'] = array_pop($keys);
                return $this->getGalleryChildren($perm, $from, $to, $noauto);
            }
            sort($keys, SORT_NUMERIC);
            foreach ($keys as $key) {
                $sorted_dates[$key] = $dates[$key];
            }
            $dates = $sorted_dates;
            $display_unit = 'day';
        } else {
            /* We are down to a specific day */
            $start = new Horde_Date($this->_date);

            /* Last second of this day */
            $end = new Horde_Date($start->timestamp());
            $end->hour = 23;
            $end->min = 59;
            $end->sec = 59;

            $where = 'image_original_date <= ' . (int)$end->timestamp() . ' AND image_original_date >= ' . (int)$start->timestamp();
            if (!empty($gallery_where)) {
                $where .= ' AND ' . $gallery_where;
            }
            $images= $ansel_storage->listImages($this->_gallery->id, $from, $to, 'image_id', $where, 'image_sort');
            $results = $ansel_storage->getImages(array('ids' => $images, 'preserve' => true));

            if ($this->_gallery->get('has_subgalleries')) {
                $images = array();
                foreach ($results as $id => $image) {
                    $image->gallery = $this->_gallery->id;
                    $images[$id] = $image;
                }
                $children[$cache_key] = $images;
            } else {
                $children[$cache_key] = $results;
            }

            return $children[$cache_key];
        }

        $results = array();
        foreach ($sorted_dates as $key => $images) {
            /* Get the new date parameter */
            switch ($display_unit) {
            case 'year':
                $date = array('year' => $key);
                break;
            case 'month':
                $date = array('year' => $this->_date['year'],
                              'month' => (int)$key);
                break;
            case 'day':
                $date = array('year' => (int)$this->_date['year'],
                              'month' => (int)$this->_date['month'],
                              'day' => (int)$key);
            }

            $obj = new Ansel_Gallery_Date($this->_gallery, $images);
            $obj->setDate($date);
            $results[$key] = $obj;
        }
        $children[$cache_key] = $results;
        if ($from > 0 || $to > 0) {
            return $this->_getArraySlice($results, $from, $to, true);
        }
        return $results;
    }

    /**
     * Return the count of this gallery's children
     *
     * @param integer $perm            The permissions to require.
     * @param boolean $galleries_only  Only include galleries, no images.
     *                                 (Ignored since this makes no sense for a
     *                                  gallery grouped by dates).
     * @param boolean $noauto          Auto navigate down to the first populated
     *                                 date grouping.
     *
     * @return integer The count of this gallery's children. The count is either
     *                 a count of of the number of date groupings (months, days,
     *                 etc..) that need to be displayed, or a count of all the
     *                 images in the current date grouping (for a specific day).
     */
    function countGalleryChildren($perm = Horde_Perms::SHOW, $galleries_only = false, $noauto = true)
    {
        $results = $this->getGalleryChildren($this->_date, 0, 0, $noauto);
        return count($results);
    }

    /**
     * Lists a slice of the image ids in this gallery.
     * In Date mode, this only makes sense if we are currently viewing a
     * specific day, otherwise we return 0.
     *
     * @param integer $from  The image to start listing.
     * @param integer $count The numer of images to list.
     *
     * @return mixed  An array of image_ids | PEAR_Error
     */
    function listImages($from = 0, $count = 0)
    {
        // FIXME: Custom query to get only image_ids when we are at a specific
        //        date.
        /* Get all of this grouping's children. */
        $children = $this->getGalleryChildren();

        /* At day level, these are all Ansel_Images */
        if (!empty($this->_date['day'])) {
            $images = array_keys($children);
        } else {
            $images = array();
            // typeof $child == Ansel_Gallery_Date
            foreach ($children as $child) {
                $images = array_merge($images, $child->_images);
            }
        }

        return $this->_getArraySlice($images, $from, $count);
    }

    /**
     * Moves images from one gallery to another. Since we're viewing by date
     * some images might belong to a subgallery so we need to take care to
     * udate the appropriate gallery data.
     *
     * @param array $images           An array of image_ids to move.
     * @param Ansel_Gallery $gallery  The Ansel_Gallery to move them to.
     *
     * @return mixed  boolean || PEAR_Error
     */
    function moveImagesTo($images, $gallery)
    {
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied(sprintf(_("Access denied moving photos to \"%s\"."), $newGallery->get('name')));
        } elseif (!$this->_gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
            throw new Horde_Exception_PermissionDenied(sprintf(_("Access denied removing photos from \"%s\"."), $gallery->get('name')));
        }

        /* Sanitize image ids, and see if we're removing our default image. */
        $ids = array();
        foreach ($images as $imageId) {
            $ids[] = (int)$imageId;
            if ($imageId == $this->_gallery->data['attribute_default']) {
                $this->_gallery->set('default', null, true);
            }
        }

        /* If we have subgalleries, we need to go the more expensive route. Note
         * we can't use $gallery->hasSubgalleries() since that would be
         * overridden here since we are in date mode and thus would return false
         */
        if ($this->_gallery->get('has_subgalleries')) {
            $gallery_ids = array();
            $images = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImages(array('ids' => $ids));
            foreach ($images as $image) {
                if (empty($gallery_ids[$image->gallery])) {
                    $gallery_ids[$image->gallery] = 1;
                } else {
                    $gallery_ids[$image->gallery]++;
                }
            }
        }

        /* Bulk update the images to their new gallery_id */
        // @TODO: Move this to Ansel_Storage::
        $result = $this->_gallery->getShareOb()->getWriteDb()->exec('UPDATE ansel_images SET gallery_id = ' . $gallery->id . ' WHERE image_id IN (' . implode(',', $ids) . ')');
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Ansel_Exception($result);
        }

        /* Update the gallery counts for each affected gallery */
        if ($this->_gallery->get('has_subgalleries')) {
            foreach ($gallery_ids as $id => $count) {
                $this->_gallery->updateImageCount($count, false, $id);
            }
        } else {
            $this->_gallery->updateImageCount(count($ids), false);
        }
        $this->_gallery->updateImageCount(count($ids), true, $gallery->id);

        /* Expire the cache since we have no reason to save() the gallery */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $gallery->id);
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $this->_gallery->id);
        }

        return true;
    }

    /**
     * Remove an image from this gallery. Note that the image might actually
     * belong to a subgallery of this gallery since we are viewing by date.
     * Need to take care of updating correct subgallery's image count etc...
     *
     * @param mixed $image      An image_id or Ansel_Image object to delete.
     * @param boolean $isStack  Image is a stack image (doesn't update count).
     *
     * @return boolean
     */
    function removeImage($image, $isStack)
    {
        /* Make sure $image is an Ansel_Image; if not, try loading it. */
        if (!($image instanceof Ansel_Image)) {
            $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($image);
        }

        /* Make sure the image is in this gallery. */
        if ($image->gallery != $this->_gallery->id) {
            $this->_getSubGalleries();
            if (!in_array($image->gallery, $this->_subGalleries)) {
                return false;
            }
        }

        /* Save this for later */
        $image_gallery = $image->gallery;

        /* Change gallery info. */
        if ($this->_gallery->data['attribute_default'] == $image->id) {
            $this->_gallery->data['attribute_default'] = null;
            $this->_gallery->data['attribute_default_type'] = 'auto';
        }

        /* Delete cached files from VFS. */
        $image->deleteCache();

        /* Delete original image from VFS. */
        try {
            $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->deleteFile($image->getVFSPath('full'),
                                              $image->getVFSName('full'));
        } catch (VFS_Exception $e) {}

        /* Delete from SQL. */
        // @TODO: Move to Horde_Storage
        $this->_gallery->getShareOb()->getWriteDb()->exec('DELETE FROM ansel_images WHERE image_id = ' . (int)$image->id);

        /* Remove any attributes */
        $this->_gallery->getShareOb()->getWriteDb()->exec('DELETE FROM ansel_image_attributes WHERE image_id = ' . (int)$image->id);

        if (!$isStack) {
            $this->_gallery->updateImageCount(1, false, $image_gallery);
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

            $result = $GLOBALS['registry']->call('forums/deleteForum', array('ansel', $image->id));
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                return false;
            }
        }

        return true;
    }

    /**
     * Helper function to get an array slice while preserving keys.
     *
     * @param unknown_type $array
     * @param unknown_type $from
     * @param unknown_type $count
     * @return unknown
     */
    function _getArraySlice($array, $from, $count, $preserve = false)
    {
        if ($from == 0 && $count == 0) {
            return $array;
        }

        return array_slice($array, $from, $count, $preserve);
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
        /* Get all of this grouping's children. */
        $children = $this->getGalleryChildren(Horde_Perms::SHOW);

        /* At day level, these are all Ansel_Images, otherwise they are
         * Ansel_Gallery_Date objects.
         */
        if (!empty($this->_date['day'])) {
            $images = $this->_getArraySlice($children, $from, $count, true);
        } else {
            // typeof $child == Ansel_Gallery_Date
            $ids = array();
            foreach ($children as $child) {
                $ids = array_merge($ids, $child->_images);
            }
            $ids = $this->_getArraySlice($ids, $from, $count);
            $images = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImages(array('ids' => $ids));
        }

        return $images;
    }

   /**
     * Checks if the gallery has any subgalleries. This will always be false
     * for a gallery in date view.
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
        return count($this->listImages());
    }

}

/**
 * A wrapper/decorator around an Ansel_Gallery to allow multiple date groupings
 * to access the same Ansel_Gallery instance. This is not a full Ansel_Gallery
 * implementation.
 *
 * TODO: For PHP5, this should be rewritten to get rid of all these gosh-darn
 * pass through functions.
 *
 * @package Ansel
 */
class Ansel_Gallery_Date {

    /* Cache the Gallery Id */
    var $id;

    /**
     * The gallery mode helper
     *
     * @var Ansel_Gallery_Mode object
     */
    var $_modeHelper;

    /* The gallery we are decorating */
    var $_gallery;

    /* An array of image ids that this "gallery" contains */
    var $_images;

    /**
     * The Ansel_Gallery_Date constructor.
     *
     * @param Ansel_Gallery $gallery  The gallery we are decorating.
     * @param array $images           An array of image ids that this grouping
     *                                contains.
     */
    function Ansel_Gallery_Date($gallery, $images = array())
    {
        $this->_gallery = $gallery;
        $this->id = $gallery->id;
        $this->_setModeHelper();
        $this->data = $this->_gallery->data;
        $this->_images = $images;
    }

    /**
     * Sets a new GalleryMode helper for this decorated gallery. The client
     * code (Ansel_GalleryMode_Date) needs to call the setDate() method on the
     * new GalleryMode_Date object before it's used.
     *
     * @return Ansel_Gallery_Mode object
     */
    function _setModeHelper()
    {
        $this->_modeHelper = new Ansel_GalleryMode_Date($this);
    }

    /**
     * Checks if the user can download the full photo
     *
     * @return boolean  Whether or not user can download full photos
     */
    function canDownload()
    {
        return $this->_gallery->canDownload();
    }

    /**
     * Copy image and related data to specified gallery.
     *
     * @param array $images           An array of image ids.
     * @param Ansel_Gallery $gallery  The gallery to copy images to.
     *
     * @return integer | PEAR_Error The number of images copied or error message
     */
    function copyImagesTo($images, $gallery)
    {
        return $this->_gallery->copyImagesTo($images, $gallery);
    }

    /**
     * Set the order of an image in this gallery.
     *
     * @param integer $imageId The image to sort.
     * @param integer $pos     The sort position of the image.
     */
    function setImageOrder($imageId, $pos)
    {
        return $this->_gallery->setImageOrder($imageId, $pos);
    }

    /**
     * Remove the given image from this gallery.
     *
     * @param mixed   $image   Image to delete. Can be an Ansel_Image
     *                         or an image ID.
     *
     * @return boolean  True on success, false on failure.
     */
    function removeImage($image, $isStack = false)
    {
        return $this->_gallery->removeImage($image, $isStack = false);
    }

    /**
     * Returns this share's owner's Identity object.
     *
     * @return Identity object for the owner of this gallery.
     */
    function getOwner()
    {
        return $this->_gallery->getOwner();
    }

    /**
     * Output the HTML for this gallery's tile.
     *
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery object
     * @param string $style          A named gallery style to use.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param array $params          Any additional parameters the Ansel_Tile
     *                               object may need.
     */
    function getTile($parent = null, $style = null, $mini = false,
                     $params = array())
    {
        if (!is_null($parent) && is_null($style)) {
            $style = $parent->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }

        return Ansel_Tile_DateGallery::getTile($this, $style, $mini, $params);
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
    function getGalleryChildren($perm = Horde_Perms::SHOW, $from = 0, $to = 0, $noauto = false)
    {
        return $this->_modeHelper->getGalleryChildren($perm, $from, $to, $noauto);
    }


    /**
     * Return the count this gallery's children
     *
     * @param integer $perm            The permissions to require.
     * @param boolean $galleries_only  Only include galleries, no images.
     *
     * @return integer The count of this gallery's children.
     */
    function countGalleryChildren($perm = Horde_Perms::SHOW, $galleries_only = false, $noauto = true)
    {
        // Need to force the date helper to not auto drill down when counting
        // from this method, since we are only called here when we are not
        // autonavigating.
        return $this->_modeHelper->countGalleryChildren($perm, $galleries_only, $noauto);
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
        return $this->_modeHelper->listImages(0, 0);
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
        return $this->_modeHelper->getImages($from, $count);
    }

    /**
     * Return the most recently added images in this gallery.
     *
     * @param integer $limit  The maximum number of images to return.
     *
     * @return mixed  An array of Ansel_Image objects | PEAR_Error
     */
    function getRecentImages($limit = 10)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getRecentImages(array($this->id),
                                                          $limit);
    }

    /**
     * Returns the image in this gallery corresponding to the given id.
     *
     * @param integer $id  The ID of the image to retrieve.
     *
     * @return Ansel_Image  The image object corresponding to the given id.
     */
    function &getImage($id)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImage($id);
    }

    /**
     * Checks if the gallery has any subgallery
     */
    function hasSubGalleries()
    {
        return $this->_modeHelper->hasSubGalleries();
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
        return count($this->_images);
    }

    /**
     * Returns the default image for this gallery.
     *
     * @param string $style  Force the use of this style, if it's available
     *                       otherwise use whatever style is choosen for this
     *                       gallery. If prettythumbs are not available then
     *                       we always use ansel_default style.
     *
     * @return mixed  The image_id of the default image or false.
     */
    function getDefaultImage($style = null)
    {
        if (count($this->_images)) {
            return reset($this->_images);
        } else {
            return 0;
        }
    }

    /**
     * Returns this gallery's tags.
     */
    function getTags()
    {
        return $this->_gallery->getTags();
    }

    /**
     * Set/replace this gallery's tags.
     *
     * @param array $tags  AN array of tag names to associate with this image.
     */
    function setTags($tags)
    {
        $this->_gallery->setTags($tags);
    }

    /**
     * Return the style definition for this gallery. Returns the first available
     * style in this order: Explicitly configured style if available, if
     * configured style is not available, use ansel_default.  If nothing has
     * been configured, the user's selected default is attempted.
     *
     * @return array  The style definition array.
     */
    function getStyle()
    {
        return $this->_gallery->getStyle();
    }

    /**
     * Return a hash key for the given view and style.
     *
     * @param string $view   The view (thumb, prettythumb etc...)
     * @param string $style  The named style.
     *
     * @return string  A md5 hash suitable for use as a key.
     */
    function getViewHash($view, $style = null)
    {
        return $this->_gallery->getViewHash($view, $style);
    }

    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    function hasPermission($userid, $permission, $creator = null)
    {
        return $this->_gallery->hasPermission($userid, $permission, $creator);
    }

    /**
     * Check user age limtation
     *
     * @return boolean
     */
    function isOldEnough()
    {
        return $this->_gallery->isOldEnough();
    }

    /**
     * Return a count of the number of children this share has
     *
     * @param integer $perm  A Horde_Perms::* constant
     * @param boolean $allLevels  Count grandchildren or just children
     *
     * @return mixed  The number of child shares || PEAR_Error
     */
    function countChildren($perm = Horde_Perms::SHOW, $allLevels = true)
    {
        return $this->_gallery->getShareOb()->countShares($GLOBALS['registry']->getAuth(), $perm, null, $this, $allLevels);
    }

    /**
     * Get all children of this share.
     *
     * @param int $perm           Horde_Perms::* constant. If NULL will return
     *                            all shares regardless of permissions.
     * @param boolean $allLevels  Return all levels.
     *
     * @return mixed  An array of Horde_Share_Object objects || PEAR_Error
     */
    function getChildren($perm = Horde_Perms::SHOW, $allLevels = true)
    {
        return $this->_gallery->getChildren($perm, $allLevels);
    }

    /**
     * Returns a child's direct parent
     *
     * @return mixed  The direct parent Horde_Share_Object or PEAR_Error
     */
    function getParent()
    {
        return $this->_gallery->getShareOb()->getParent($this);
    }

    /**
     * Get all of this share's parents.
     *
     * @return array()  An array of Horde_Share_Objects
     */
    function getParents()
    {
        return $this->_gallery->getParents();

    }

    function get($attribute)
    {
        return $this->_gallery->get($attribute);
    }

    function getDate()
    {
        return $this->_modeHelper->getDate();
    }

    function setDate($date)
    {
        $this->_modeHelper->setDate($date);
    }

}
