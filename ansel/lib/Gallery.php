<?php
/**
 * Class to encapsulate a single gallery. Implemented as an extension of
 * the Horde_Share_Object class.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Gallery extends Horde_Share_Object_sql_hierarchical
{
    /**
     * Cache the Gallery Id - to match the Ansel_Image interface
     */
    var $id;

    /**
     * The gallery mode helper
     *
     * @var Ansel_Gallery_Mode object
     */
    var $_modeHelper;

    /**
     *
     */
    function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_shareOb']);
        unset($properties['_modeHelper']);
        $properties = array_keys($properties);
        return $properties;
    }

    function __wakeup()
    {
        $this->setShareOb($GLOBALS['ansel_storage']->shares);
        $mode = $this->get('view_mode');
        $this->_setModeHelper($mode);
    }

    /**
     * The Ansel_Gallery constructor.
     *
     * @param string $name  The name of the gallery
     */
    function Ansel_Gallery($attributes = array())
    {
        /* Existing gallery? */
        if (!empty($attributes['share_id'])) {
            $this->id = (int)$attributes['share_id'];
        }

        /* Pass on up the chain */
        parent::Horde_Share_Object_sql_hierarchical($attributes);
        $this->setShareOb($GLOBALS['ansel_storage']->shares);
        $mode = isset($attributes['attribute_view_mode']) ? $attributes['attribute_view_mode'] : 'Normal';
        $this->_setModeHelper($mode);
    }

    /**
     * Check for special capabilities of this gallery.
     *
     */
    function hasFeature($feature)
    {

        // First check for purely Ansel_Gallery features
        // Currently we have none of these.

        // Delegate to the modeHelper
        return $this->_modeHelper->hasFeature($feature);

    }

    /**
     * Simple factory to retrieve the proper mode object.
     *
     * @param string $type  The mode to use
     *
     * @return Ansel_Gallery_Mode object
     */
    function _setModeHelper($type = 'Normal')
    {
        $type = basename($type);
        $class = 'Ansel_GalleryMode_' . $type;
        $this->_modeHelper = new $class($this);
        $this->_modeHelper->init();
    }

    /**
     * Checks if the user can download the full photo
     *
     * @return boolean  Whether or not user can download full photos
     */
    function canDownload()
    {
        if (Horde_Auth::getAuth() == $this->data['share_owner'] || Horde_Auth::isAdmin('ansel:admin')) {
            return true;
        }

        switch ($this->data['attribute_download']) {
        case 'all':
            return true;

        case 'authenticated':
            return Horde_Auth::isAuthenticated();

        case 'edit':
            return $this->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT);

        case 'hook':
            return Horde::callHook('_ansel_hook_can_download', array($this->id));

        default:
            return false;
        }
    }

    /**
     * Saves any changes to this object to the backend permanently.
     *
     * @return mixed true || PEAR_Error on failure.
     */
    function _save()
    {
        // Check for invalid characters in the slug.
        if (!empty($this->data['attribute_slug']) &&
            preg_match('/[^a-zA-Z0-9_@]/', $this->data['attribute_slug'])) {

            return PEAR::raiseError(
                sprintf(_("Could not save gallery, the slug, \"%s\", contains invalid characters."),
                        $this->data['attribute_slug']));
        }

        // Check for slug uniqueness
        $slugGalleryId = $GLOBALS['ansel_storage']->slugExists($this->data['attribute_slug']);
        if ($slugGalleryId > 0 && $slugGalleryId <> $this->id) {
            return PEAR::raiseError(sprintf(_("Could not save gallery, the slug, \"%s\", already exists."),
                                            $this->data['attribute_slug']));
        }

        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['cache']->expire('Ansel_Gallery' . $this->id);
        }
        return parent::_save();
    }

    /**
     * Update the gallery image count.
     *
     * @param integer $images      Number of images in action
     * @param boolean $add         Action to take (add or remove)
     * @param integer $gallery_id  Gallery id to update images for
     */
    function _updateImageCount($images, $add = true, $gallery_id = null)
    {
        // We do the query directly here to avoid having to instantiate a
        // gallery object just to increment/decrement one value in the table.
        $sql = 'UPDATE ' . $this->_shareOb->_table
            . ' SET attribute_images = attribute_images '
            . ($add ? ' + ' : ' - ') . $images . ' WHERE share_id = '
            . ($gallery_id ? $gallery_id : $this->id);

        // Make sure to update the local value as well, so it doesn't get
        // overwritten by any other updates from ->set() calls.
        if (is_null($gallery_id) || $gallery_id === $this->id) {
            if ($add) {
                $this->data['attribute_images'] += $images;
            } else {
                $this->data['attribute_images'] -= $images;
            }
        }

        /* Need to expire the cache for the gallery that was changed */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $id = (is_null($gallery_id) ? $this->id : $gallery_id);
            $GLOBALS['cache']->expire('Ansel_Gallery' . $id);
        }

        return $this->_shareOb->_write_db->exec($sql);

    }

    /**
     * Add an image to this gallery.
     *
     * @param array $image_data  The image to add. Required keys include
     *                           'image_caption', and 'data'. Optional keys
     *                           include 'image_filename' and 'image_type'
     *
     * @param boolean $default   Make this image the new default tile image.
     *
     * @return integer  The id of the new image.
     */
    function addImage($image_data, $default = false)
    {
        global $conf;

        /* Normal is the only view mode that can accurately update gallery counts */
        $vMode = $this->get('view_mode');
        if ($vMode != 'Normal') {
            $this->_setModeHelper('Normal');
        }

        $resetStack = false;
        if (!isset($image_data['image_filename'])) {
            $image_data['image_filename'] = 'Untitled';
        }
        $image_data['gallery_id'] = $this->id;
        $image_data['image_sort'] = $this->countImages();

        /* Create the image object */
        $image = new Ansel_Image($image_data);
        $result = $image->save();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        if (empty($image_data['image_id'])) {
            $this->_updateImageCount(1);
            if ($this->countImages() < 5) {
                $resetStack = true;
            }
        }

        /* Should this be the default image? */
        if (!$default && $this->data['attribute_default_type'] == 'auto') {
            $this->data['attribute_default'] = $image->id;
            $resetStack = true;
        } elseif ($default) {
            $this->data['attribute_default'] = $image->id;
            $this->data['default_type'] = 'manual';
        }

        /* Reset the gallery default image stacks if needed. */
        if ($resetStack) {
            $this->clearStacks();
        }

        /* Update the modified flag and save gallery changes */
        $this->data['attribute_last_modified'] = time();

        /* Save all changes to the gallery */
        $this->save();

        /* Return to the proper view mode */
        if ($vMode != 'Normal') {
            $this->_setModeHelper($vMode);
        }

        /* Return the ID of the new image. */
        return $image->id;
    }

    /**
     * Clear all of this gallery's default image stacks from the VFS and the
     * gallery's data store.
     *
     */
    function clearStacks()
    {
        $ids = @unserialize($this->data['attribute_default_prettythumb']);
        if (is_array($ids)) {
            foreach ($ids as $imageId) {
                $this->removeImage($imageId, true);
            }
        }

        // Using the set function here so we can efficently update the db
        $this->set('default_prettythumb', '', true);
    }

    /**
     * Removes all generated and cached 'prettythumb' thumbnails for this
     * gallery
     *
     */
    function clearThumbs()
    {
        $images = $this->listImages();
        foreach ($images as $id) {
            $image = $this->getImage($id);
            $image->deleteCache('prettythumb');
        }
    }

    /**
     * Removes all generated and cached views for this gallery
     *
     */
    function clearViews()
    {
        $images = $this->listImages();
        foreach ($images as $id) {
            $image = $this->getImage($id);
            $image->deleteCache('all');
        }
    }

    /**
     * Move images from this gallery to a new gallery.
     *
     * @param array $images          An array of image ids.
     * @param Ansel_Gallery $gallery The gallery to move the images to.
     *
     * @return integer | PEAR_Error The number of images moved, or an error message.
     */
    function moveImagesTo($images, $gallery)
    {
        return $this->_modeHelper->moveImagesTo($images, $gallery);
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
        if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            return PEAR::raiseError(
                sprintf(_("Access denied copying photos to \"%s\"."),
                          $gallery->get('name')));
        }

        $db = $this->_shareOb->_write_db;
        $imgCnt = 0;
        foreach ($images as $imageId) {
            $img = &$this->getImage($imageId);
            // Note that we don't pass the tags when adding the image..see below
            $newId = $gallery->addImage(array(
                               'image_caption' => $img->caption,
                               'data' => $img->raw(),
                               'image_filename' => $img->filename,
                               'image_type' => $img->getType(),
                               'image_uploaded_date' => $img->uploaded));
            if (is_a($newId, 'PEAR_Error')) {
                return $newId;
            }
            /* Copy any tags */
            // Since we know that the tags already exist, no need to
            // go through Ansel_Tags::writeTags() - this saves us a SELECT query
            // for each tag - just write the data into the DB ourselves.
            $tags = $img->getTags();
            $query = $this->_shareOb->_write_db->prepare('INSERT INTO ansel_images_tags (image_id, tag_id) VALUES(' . $newId . ',?);');
            if (is_a($query, 'PEAR_Error')) {
                return $query;
            }
            foreach ($tags as $tag_id => $tag_name) {
                $result = $query->execute($tag_id);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }
            $query->free();

            /* exif data */
            // First check to see if the exif data was present in the raw data.
            $count = $db->queryOne('SELECT COUNT(image_id) FROM ansel_image_attributes WHERE image_id = ' . (int) $newId . ';');
            if ($count == 0) {
                $exif = $db->queryAll('SELECT attr_name, attr_value FROM ansel_image_attributes WHERE image_id = ' . (int) $imageId . ';',null, MDB2_FETCHMODE_ASSOC);
                if (is_array($exif) && count($exif) > 0) {
                    $insert = $db->prepare('INSERT INTO ansel_image_attributes (image_id, attr_name, attr_value) VALUES (?, ?, ?)');
                    if (is_a($insert, 'PEAR_Error')) {
                        return $insert;
                    }
                    foreach ($exif as $attr){
                        $result = $insert->execute(array($newId, $attr['attr_name'], $attr['attr_value']));
                        if (is_a($result, 'PEAR_Error')) {
                            return $result;
                        }
                    }
                    $insert->free();
                }
            }
            ++$imgCnt;
        }

        return $imgCnt;
    }

    /**
     * Set the order of an image in this gallery.
     *
     * @param integer $imageId The image to sort.
     * @param integer $pos     The sort position of the image.
     */
    function setImageOrder($imageId, $pos)
    {
        return $this->_shareOb->_write_db->exec('UPDATE ansel_images SET image_sort = ' . (int)$pos . ' WHERE image_id = ' . (int)$imageId);
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
        return $this->_modeHelper->removeImage($image, $isStack);
    }

    /**
     * Returns this share's owner's Identity object.
     *
     * @return Identity object for the owner of this gallery.
     */
    function getOwner()
    {
        require_once 'Horde/Identity.php';
        $identity = Identity::singleton('none', $this->data['share_owner']);
        return $identity;
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

        if (!empty($view_url)) {
            $view_url = str_replace('%g', $this->id, $view_url);
        }

        return Ansel_Tile_Gallery::getTile($this, $style, $mini, $params);
    }

    /**
     * Get the children of this gallery.
     *
     * @param integer $perm    The permissions to limit to.
     * @param integer $from    The child to start at.
     * @param integer $to      The child to end with.
     * @param boolean $noauto  Prevent auto
     *
     * @return A mixed array of Ansel_Gallery and Ansel_Image objects that are
     *         children of this gallery.
     */
    function getGalleryChildren($perm = PERMS_SHOW, $from = 0, $to = 0, $noauto = true)
    {
        return $this->_modeHelper->getGalleryChildren($perm, $from, $to, $noauto);
    }


    /**
     * Return the count of this gallery's children
     *
     * @param integer $perm            The permissions to require.
     * @param boolean $galleries_only  Only include galleries, no images.
     *
     * @return integer The count of this gallery's children.
     */
    function countGalleryChildren($perm = PERMS_SHOW, $galleries_only = false, $noauto = true)
    {
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
        return $this->_modeHelper->listImages($from, $count);
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
        return $GLOBALS['ansel_storage']->getRecentImages(array($this->id),
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
        return $GLOBALS['ansel_storage']->getImage($id);
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
        return $this->_modeHelper->countImages($subgalleries);
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
       // Check for explicitly requested style
        if (!is_null($style)) {
            $gal_style = Ansel::getStyleDefinition($style);
        } else {
            // Use gallery's default.
            $gal_style = $this->getStyle();
            if (!isset($GLOBALS['ansel_styles'][$gal_style['name']])) {
                $gal_style = $GLOBALS['ansel_styles']['ansel_default'];
            }
        }
        Horde::logMessage(sprintf("using gallery style: %s in Ansel::getDefaultImage()", $gal_style['name']), __FILE__, __LINE__, PEAR_LOG_DEBUG);
        if (!empty($gal_style['default_galleryimage_type']) &&
            $gal_style['default_galleryimage_type'] != 'plain') {

            $thumbstyle = $gal_style['default_galleryimage_type'];
            $styleHash = $this->_getViewHash($thumbstyle, $style);

            // First check for the existence of a default image in the style
            // we are looking for.
            if (!empty($this->data['attribute_default_prettythumb'])) {
                $thumbs = @unserialize($this->data['attribute_default_prettythumb']);
            }
            if (!isset($thumbs) || !is_array($thumbs)) {
                $thumbs = array();
            }

            if (!empty($thumbs[$styleHash])) {
                return $thumbs[$styleHash];
            }

            // Don't already have one, must generate it.
            $params = array('gallery' => $this, 'style' => $gal_style);
            $iview = Ansel_ImageView::factory(
                $gal_style['default_galleryimage_type'], $params);

            if (!is_a($iview, 'PEAR_Error')) {
                $img = $iview->create();
                if (!is_a($img, 'PEAR_Error')) {
                     // Note the gallery_id is negative for generated stacks
                     $iparams = array('image_filename' => $this->get('name'),
                                      'image_caption' => $this->get('name'),
                                      'data' => $img->raw(),
                                      'image_sort' => 0,
                                      'gallery_id' => -$this->id);
                     $newImg = new Ansel_Image($iparams);
                     $newImg->save();
                     $prettyData = serialize(
                         array_merge($thumbs,
                                     array($styleHash => $newImg->id)));

                     $this->set('default_prettythumb', $prettyData, true);
                     return $newImg->id;
                } else {
                    Horde::logMessage($img, __FILE__, __LINE__, PEAR_LOG_ERR);
                }
            } else {
                // Might not support the requested style...try ansel_default
                // but protect against infinite recursion.
                Horde::logMessage($iview, __FILE__, __LINE__, PEAR_LOG_DEBUG);
                if ($style != 'ansel_default') {
                    return $this->getDefaultImage('ansel_default');
                }
                Horde::logMessage($iview, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        } else {
            // We are just using an image thumbnail for the gallery default.
            if ($this->countImages()) {
                if (!empty($this->data['attribute_default']) &&
                    $this->data['attribute_default'] > 0) {

                    return $this->data['attribute_default'];
                }
                $keys = $this->listImages();
                if (is_a($keys, 'PEAR_Error')) {
                    return $keys;
                }
                $this->data['attribute_default'] = $keys[count($keys) - 1];
                $this->data['attribute_default_type'] = 'auto';
                $this->save();
                return $keys[count($keys) - 1];
            }

            if ($this->hasSubGalleries()) {
                // Fall through to a default image of a sub gallery.
                $galleries = $GLOBALS['ansel_storage']->listGalleries(
                    PERMS_SHOW, null, $this, false);
                if ($galleries && !is_a($galleries, 'PEAR_Error')) {
                    foreach ($galleries as $galleryId => $gallery) {
                        if ($default_img = $gallery->getDefaultImage($style)) {
                            return $default_img;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Returns this gallery's tags.
     */
    function getTags() {
        if ($this->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
            return Ansel_Tags::readTags($this->id, 'gallery');
        } else {
            return PEAR::raiseError(_("Access denied viewing this gallery."));
        }
    }

    /**
     * Set/replace this gallery's tags.
     *
     * @param array $tags  AN array of tag names to associate with this image.
     */
    function setTags($tags)
    {
        if ($this->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            return Ansel_Tags::writeTags($this->id, $tags, 'gallery');
        } else {
            return PEAR::raiseError(_("Access denied adding tags to this gallery."));
        }
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
        if (empty($this->data['attribute_style'])) {
            $style = $GLOBALS['prefs']->getValue('default_gallerystyle');
        } else {
            $style = $this->data['attribute_style'];
        }
        return Ansel::getStyleDefinition($style);

    }

    /**
     * Return a hash key for the given view and style.
     *
     * @param string $view   The view (thumb, prettythumb etc...)
     * @param string $style  The named style.
     *
     * @return string  A md5 hash suitable for use as a key.
     */
    function _getViewHash($view, $style = null)
    {
        if (is_null($style)) {
            $style = $this->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }
        if ($view != 'screen' && $view != 'thumb' && $view != 'mini' &&
            $view != 'full') {

            $view = md5($style['thumbstyle'] . '.' . $style['background']);
        }
        return $view;
    }
    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A PERMS_* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    function hasPermission($userid, $permission, $creator = null)
    {
        if ($userid == $this->data['share_owner'] ||
            Horde_Auth::isAdmin('ansel:admin')) {

            return true;
        }


        return $GLOBALS['perms']->hasPermission($this->getPermission(),
                                                $userid, $permission, $creator);
    }

    /**
     * Check user age limtation
     *
     * @return boolean
     */
    function isOldEnough()
    {
        if ($this->data['share_owner'] == Horde_Auth::getAuth() ||
            empty($GLOBALS['conf']['ages']['limits']) ||
            empty($this->data['attribute_age'])) {

            return true;
        }

        // Do we have the user age already cheked?
        if (!isset($_SESSION['ansel']['user_age'])) {
            $_SESSION['ansel']['user_age'] = 0;
        } elseif ($_SESSION['ansel']['user_age'] >= $this->data['attribute_age']) {
            return true;
        }

        // Can we hook user's age?
        if ($GLOBALS['conf']['ages']['hook'] && Horde_Auth::isAuthenticated()) {
            $result = Horde::callHook('_ansel_hook_user_age');
            if (is_int($result)) {
                $_SESSION['ansel']['user_age'] = $result;
            }
        }

        return ($_SESSION['ansel']['user_age'] >= $this->data['attribute_age']);
    }

    /**
     * Determine if we need to unlock a password protected gallery
     *
     * @return boolean
     */
    function hasPasswd()
    {
        if (Horde_Auth::getAuth() == $this->get('owner') || Horde_Auth::isAdmin('ansel:admin')) {
            return false;
        }

        $passwd = $this->get('passwd');
        if (empty($passwd) ||
            (!empty($_SESSION['ansel']['passwd'][$this->id])
                && $_SESSION['ansel']['passwd'][$this->id] = md5($this->get('passwd')))) {
            return false;
        }

        return true;
    }

    /**
     * Sets this gallery's parent gallery.
     *
     * @TODO: Check how this interacts with date galleries - shouldn't be able
     *        to remove a subgallery from a date gallery anyway, but just incase
     * @param mixed $parent    An Ansel_Gallery or a gallery_id.
     *
     * @return mixed  Ture || PEAR_Error
     */
    function setParent($parent)
    {
        /* Make sure we have a gallery object */
        if (!is_null($parent) && !is_a($parent, 'Ansel_Gallery')) {
            $parent = $GLOBALS['ansel_storage']->getGallery($parent);
            if (is_a($parent, 'PEAR_Error')) {
                return $parent;
            }
        }

        /* Check this now since we don't know if we are updating the DB or not */
        $old = $this->getParent();
        $reset_has_subgalleries = false;
        if (!is_null($old)) {
            $cnt = $old->countGalleryChildren(PERMS_READ, true);
            if ($cnt == 1) {
                /* Count is 1, and we are about to delete it */
                $reset_has_subgalleries = true;
            }
        }

        /* Call the parent class method */
        $result = parent::setParent($parent);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Tell the parent the good news */
        if (!is_null($parent) && !$parent->get('has_subgalleries')) {
            return $parent->set('has_subgalleries', '1', true);
        }
        Horde::logMessage('Ansel_Gallery parent successfully set', __FILE__,
                          __LINE__, PEAR_LOG_DEBUG);

       /* Gallery parent changed, safe to change the parent's attributes */
       if ($reset_has_subgalleries) {
           $old->set('has_subgalleries', 0, true);
       }

        return true;
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     * @param boolean $update    Commit only this change to storage.
     *
     * @return mixed  True if setting the attribute did succeed, a PEAR_Error
     *                otherwise.
     */
    function set($attribute, $value, $update = false)
    {
        /* Translate the keys */
        if ($attribute == 'owner') {
            $driver_key = 'share_owner';
        } else {
            $driver_key = 'attribute_' . $attribute;
        }

        if ($driver_key == 'attribute_view_mode' &&
            !empty($this->data[$driver_key]) &&
            $value != $this->data[$driver_key]) {

            $mode = isset($attributes['attribute_view_mode']) ? $attributes['attribute_view_mode'] : 'Normal';
            $this->_setModeHelper($mode);
        }

        $this->data[$driver_key] = $value;

        /* Update the backend, but only this current change */
        if ($update) {
            $db = $this->_shareOb->_write_db;
            // Manually convert the charset since we're not going through save()
            $data = $this->_shareOb->_toDriverCharset(array($driver_key => $value));
            $query = $db->prepare('UPDATE ' . $this->_shareOb->_table . ' SET ' . $driver_key . ' = ? WHERE share_id = ?', null, MDB2_PREPARE_MANIP);
            if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                $GLOBALS['cache']->expire('Ansel_Gallery' . $this->id);
            }
            $result = $query->execute(array($data[$driver_key], $this->id));
            $query->free();

            return $result;
        }

        return true;
    }

    function setDate($date)
    {
        $this->_modeHelper->setDate($date);
    }

    function getDate()
    {
        return $this->_modeHelper->getDate();
    }

    /**
     * Get an array describing where this gallery is in a breadcrumb trail.
     *
     * @return  An array of 'title' and 'navdata' hashes with the [0] element
     *          being the deepest part.
     */
    function getGalleryCrumbData()
    {
        return $this->_modeHelper->getGalleryCrumbData();
    }

}
