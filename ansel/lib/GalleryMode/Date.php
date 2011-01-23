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
class Ansel_GalleryMode_Date extends Ansel_GalleryMode_Base
{
    /**
     * The date part array for the current grouping.
     *
     * @var array
     */
    protected $_date = array();

    /**
     * Supported features
     *
     * @var array
     */
    protected $_features = array('slideshow', 'zipdownload', 'upload');

    /**
     * The subgalleries whose images need to be included in this date grouping.
     *
     * @var array
     */
    protected $_subGalleries = null;

    /**
     * See if a feature is supported.
     *
     * @param string $feature  The feature
     *
     * @return boolean
     */
    public function hasFeature($feature)
    {
        /* First, some special cases */
        switch ($feature) {
        case 'sort_images':
        case 'image_captions':
        case 'faces':
            /* Only allowed when we are on a specific day */
            return !empty($this->_date['day']);

        default:
            return parent::hasFeature($feature);
        }
    }

    /**
     * Get an array describing where this gallery is in a breadcrumb trail.
     *
     * @return  An array of 'title' and 'navdata' hashes with the [0] element
     *          being the deepest part.
     */
    public function getGalleryCrumbData()
    {
        $year = !empty($this->_date['year']) ? $this->_date['year'] : 0;
        $month = !empty($this->_date['month']) ? $this->_date['month'] : 0;
        $day = !empty($this->_date['day']) ? $this->_date['day'] : 0;
        $trail = array();

        /* Do we have any date parts? */
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

        $text = htmlspecialchars($this->_gallery->get('name'));
        $navdata = array('view' => 'Gallery',
                         'gallery' => $this->_gallery->id,
                         'slug' => $this->_gallery->get('slug'));

        $trail[] = array('title' => $text, 'navdata' => $navdata);

        return $trail;
    }

    /**
     * Getter for date
     *
     * @return array  A date parts array.
     */
    public function getDate()
    {
        return $this->_date;
    }

    /**
     * Setter for date
     *
     * @param array $date
     */
    public function setDate($date = array())
    {
        $this->_date = $date;
    }

    /**
     * Get the children of this gallery.
     *
     * @param integer $perm    The permissions to limit to.
     * @param integer $from    The child to start at.
     * @param integer $to      The child to end with.
     * @param boolean $noauto  Whether or not to automatically drill down to the
     *                         first grouping with more then one group.
     *
     * @return array A mixed array of Ansel_Gallery_Decorator_Date and Ansel_Image objects.
     */
    public function getGalleryChildren($perm = Horde_Perms::SHOW, $from = 0, $to = 0, $noauto = false)
    {
        /* Cache the results */
        static $children = array();

        /* Ansel Storage */
        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');

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
            $images = $ansel_storage->listImages($this->_gallery->id, $from, $to, 'image_id', $where, 'image_sort');
            if ($images) {
                $results = $ansel_storage->getImages(array('ids' => $images, 'preserve' => true));
            } else {
                $results = array();
            }

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

            $obj = new Ansel_Gallery_Decorator_Date($this->_gallery, $images);
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
    public function countGalleryChildren($perm = Horde_Perms::SHOW, $galleries_only = false, $noauto = true)
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
     * @return array  An array of image_ids
     */
    public function listImages($from = 0, $count = 0)
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
            // typeof $child == Ansel_Gallery_Decorator_Date
            foreach ($children as $child) {
                $images = array_merge($images, $child->getImagesByGrouping());
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
     * @return boolean
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

        /* If we have subgalleries, we need to go the more expensive route. Note
         * we can't use $gallery->hasSubgalleries() since that would be
         * overridden here since we are in date mode and thus would return false
         */
        if ($this->_gallery->get('has_subgalleries')) {
            $gallery_ids = array();
            $images = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImages(array('ids' => $ids));
            foreach ($images as $image) {
                if (empty($gallery_ids[$image->gallery])) {
                    $gallery_ids[$image->gallery] = 1;
                } else {
                    $gallery_ids[$image->gallery]++;
                }
            }
        }

        /* Bulk update the images to their new gallery_id */
        $GLOBALS['injector']->getInstance('Ansel_Storage')->setImagesGallery($ids, $gallery->id);

        /* Update the gallery counts for each affected gallery */
        if ($this->_gallery->get('has_subgalleries')) {
            foreach ($gallery_ids as $id => $count) {
                $GLOBALS['injector']->getInstance('Ansel_Storage')
                    ->getGallery($id)
                    ->updateImageCount($count, false);
            }
        } else {
            $this->_gallery->updateImageCount(count($ids), false);
        }
        $gallery->updateImageCount(count($ids), true);

        /* Expire the cache since we have no reason to save() the gallery */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $gallery->id);
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $this->_gallery->id);
        }

        return count($ids);
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
     * @throws Horde_Exception_NotFound
     */
    public function removeImage($image, $isStack)
    {
        /* Make sure $image is an Ansel_Image; if not, try loading it. */
        if (!($image instanceof Ansel_Image)) {
            $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($image);
        }

        /* Make sure the image is in this gallery. */
        if ($image->gallery != $this->_gallery->id) {
            $this->_getSubGalleries();
            if (!in_array($image->gallery, $this->_subGalleries)) {
                throw new Horde_Exception_NotFound(_("Image not found in gallery."));
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
            $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->deleteFile($image->getVFSPath('full'),
                                              $image->getVFSName('full'));
        } catch (VFS_Exception $e) {}

        /* Delete from storage */
        $GLOBALS['injector']->getInstance('Ansel_Storage')->removeImage($image->id);

        if (!$isStack) {
            $GLOBALS['injector']->getInstance('Ansel_Storage')
                    ->getGallery($image_gallery)
                    ->updateImageCount(1, false);
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
            try {
                $GLOBALS['registry']->call('forums/deleteForum', array('ansel', $image->id));
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, 'ERR');
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
        if (!empty($this->_date['day'])) {
            // Get all of this grouping's children. At day level, these are all
            // Ansel_Images.
            $children = $this->getGalleryChildren(Horde_Perms::SHOW);
            return $this->_getArraySlice($children, $from, $count, true);
        } else {
            // We don't want to work with any images at this level in a DateMode
            // gallery.
            return array();
        }
    }

    /**
     * Checks if the gallery has any subgalleries. This will always be false
     * for a gallery in date view.
     *
     * @return boolean
     */
    public function hasSubGalleries()
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
    public function countImages($subgalleries = false)
    {
        return count($this->listImages());
    }

    /**
     * Helper function to get an array slice while preserving keys.
     *
     * @param unknown_type $array
     * @param unknown_type $from
     * @param unknown_type $count
     * @return unknown
     */
    protected function _getArraySlice($array, $from, $count, $preserve = false)
    {
        if ($from == 0 && $count == 0) {
            return $array;
        }

        return array_slice($array, $from, $count, $preserve);
    }

    /**
     * Get this gallery's subgalleries. Populates the private member
     *  _subGalleries
     *
     * @return void
     */
    protected function _getSubGalleries()
    {
        if (!is_array($this->_subGalleries)) {
            /* Get a list of all the subgalleries */
            $subs = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->listGalleries(array('parent' => $this->_gallery));
            foreach ($subs as $sub) {
                $this->_subGalleries[] = $sub->getId();
            }
        }
    }
}
