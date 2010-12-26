<?php
/**
 * Class to encapsulate a single gallery. Implemented as an extension of
 * the Horde_Share_Object class.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Gallery extends Horde_Share_Object_Sql_Hierarchical implements Serializable
{
    /**
     * The gallery mode helper
     *
     * @var Ansel_GalleryMode_* object
     */
    protected $_modeHelper;

    /**
     * The Ansel_Gallery constructor.
     *
     * @param string $name  The name of the gallery
     */
    public function __construct($attributes = array())
    {
        /* Pass on up the chain */
        parent::__construct($attributes);
        $GLOBALS['injector']->getInstance('Ansel_Storage')->shares->initShareObject($this);
        $this->_setModeHelper(isset($attributes['attribute_view_mode']) ? $attributes['attribute_view_mode'] : 'Normal');
    }

    /**
     * Helper for accessing the gallery id
     *
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property) {
        case 'id':
            return $this->getId();
        default:
            return null;
        }
    }

    public function get($property)
    {
        $value = parent::get($property);
        if ($property == 'style') {
            $value = unserialize($value);
        }

        return $value;
    }

    /**
     * Check for special capabilities of this gallery.
     *
     * @return boolean
     */
    public function hasFeature($feature)
    {
        // First check for purely Ansel_Gallery features
        // Currently we have none of these.

        // Delegate to the modeHelper
        return $this->_modeHelper->hasFeature($feature);
    }

    /**
     * Simple factory to set the proper mode object.
     *
     * @param string $type  The mode to use
     *
     * @return Ansel_Gallery_Mode object
     */
    protected function _setModeHelper($type = 'Normal')
    {
        $type = basename($type);
        $class = 'Ansel_GalleryMode_' . $type;
        $this->_modeHelper = new $class($this);
    }

    /**
     * Checks if the user can download the full photo
     *
     * @return boolean  Whether or not user can download full photos
     */
    public function canDownload()
    {
        if ($GLOBALS['registry']->getAuth() &&
            ($GLOBALS['registry']->getAuth() == $this->data['share_owner'] ||
             $GLOBALS['registry']->isAdmin(array('permission' => 'ansel:admin')))) {
            return true;
        }

        switch ($this->data['attribute_download']) {
        case 'all':
            return true;

        case 'authenticated':
            return $GLOBALS['registry']->isAuthenticated();

        case 'edit':
            return $this->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);

        case 'hook':
            return Horde::callHook('_ansel_hook_can_download', array($this->id));

        default:
            return false;
        }
    }

    /**
     * Saves any changes to this object to the backend permanently.
     *
     * @return boolean
     */
    protected function _save()
    {
        // Check for invalid characters in the slug.
        if (!empty($this->data['attribute_slug']) &&
            preg_match('/[^a-zA-Z0-9_@]/', $this->data['attribute_slug'])) {

            throw new InvalidArgumentException(
                sprintf(_("Could not save gallery, the slug, \"%s\", contains invalid characters."),
                        $this->data['attribute_slug']));
        }

        // Check for slug uniqueness
        $slugGalleryId = $GLOBALS['injector']->getInstance('Ansel_Storage')->slugExists($this->data['attribute_slug']);
        if ($slugGalleryId > 0 && $slugGalleryId <> $this->id) {
            throw InvalidArgumentException(
                sprintf(_("Could not save gallery, the slug, \"%s\", already exists."),
                        $this->data['attribute_slug']));
        }

        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $this->id);
        }

        return parent::_save();
    }

    /**
     * Update the gallery image count.
     *
     * @param integer $images      Number of images in action
     * @param boolean $add         True if adding, false if removing
     *
     * @return boolean true on success
     * @throws Ansel_Exception
     */
    public function updateImageCount($images, $add = true)
    {
        /* Updating self */
        if ($add) {
            $this->data['attribute_images'] += $images;
        } else {
            $this->data['attribute_images'] -= $images;
        }
        try {
            $this->save();
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Ansel_Exception($e);
        }

        /* Make sure we get rid of key image/stacks if no more images */
        if (!$this->data['attribute_images']) {
            $this->resetKeyImage();
        }

        /* Need to expire the cache for the gallery that was changed */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $this->id);
        }

        return true;
    }

    /**
     * Adds an Ansel_Image object to this gallery.
     *
     * @param Ansel_Image $image  The ansel image object to add
     * @param boolean $default    Set this image as the gallery's key image.
     *
     * @return integer  The new image id
     */
    public function addImageObject(Ansel_Image $image, $default = false)
    {
        /* Make sure it's taken as a new image */
        $image->id = null;
        $image->gallery = $this->getId();
        $image->sort = $this->countImages();
        $image->save();
        $this->updateImageCount(1);

        /* Should this be the key image? */
        if ($default) {
            $this->data['attribute_default'] = $image->id;
            $this->clearStacks();
        }

        /* Save all changes to the gallery */
        $this->save();

        return $image->id;
    }

    /**
     * Add an image to this gallery.
     *
     * @param array $image_data  The image to add. Keys include:
     *  <pre>
     *    image_filename   - The filename of the image [REQUIRED].
     *    data             - The binary image data [REQUIRED]
     *    image_caption    - The caption/description. Defaults to filename.
     *    image_type       - The MIME type of the image. Attempts to detect.
     *  </pre>
     *                           'image_caption', and 'data'. Optional keys
     *                           include 'image_filename' and 'image_type'
     *
     * @param boolean $default   Make this image the new default tile image.
     *
     * @return integer  The id of the new image.
     */
    public function addImage($image_data, $default = false)
    {
        global $conf;

        /* Normal is the only view mode that can accurately update gallery counts */
        $vMode = $this->get('view_mode');
        if ($vMode != 'Normal') {
            $this->_setModeHelper('Normal');
        }

        $resetStack = false;
        if (empty($image_data['image_caption'])) {
            $image_data['image_caption'] = $image_data['image_filename'];
        }
        $image_data['gallery_id'] = $this->id;
        $image_data['image_sort'] = $this->countImages();

        /* Create the image object */
        $image = new Ansel_Image($image_data);

        /* Check for a supported multi-page image */
        if ($image->isMultiPage() === true) {
            $params['name'] = $image->getImagePageCount() . ' page image: ' . $image->filename;
            $mGallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->createGallery($params, $this->getPermission(), $this->getId());
            $i = 1;
            foreach ($image as $page) {
                $page->caption = sprintf(_("Page %d"), $i++);
                $mGallery->addImageObject($page);
            }
            $mGallery->save();

            return $page->id;
        }

        /* If this was a single, normal image, continue */
        $result = $image->save();
        if (empty($image_data['image_id'])) {
            $this->updateImageCount(1);
            if ($this->countImages() < 5) {
                $resetStack = true;
            }
        }

        /* Should this be the key image? */
        if (!$default && $this->data['attribute_default_type'] == 'auto') {
            $this->data['attribute_default'] = $image->id;
            $resetStack = true;
        } elseif ($default) {
            $this->data['attribute_default'] = $image->id;
            $this->data['default_type'] = 'manual';
        }

        /* Reset the gallery key image stacks if needed. */
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
     * Clear all of this gallery's key image stacks from the VFS and the
     * gallery's data store.
     *
     * @return void
     */
    public function clearStacks()
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
     * @return void
     */
    public function clearThumbs()
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
     * @return void
     */
    public function clearViews()
    {
        $images = $this->listImages();
        foreach ($images as $id) {
            $image = $this->getImage($id);
            $image->deleteCache('all');
        }
    }

    /**
     * Reset the gallery's key image. This will force Ansel to attempt to fetch
     * a new key image the next time one is requested.
     *
     */
    public function resetKeyImage()
    {
        $this->clearStacks();
        $this->set('default', 0);
        $this->set('default_type', 'auto');
        $this->save();
    }

    /**
     * Move images from this gallery to a new gallery.
     *
     * @param array $images          An array of image ids.
     * @param Ansel_Gallery $gallery The gallery to move the images to.
     *
     * @return integer | PEAR_Error The number of images moved, or an error message.
     */
    public function moveImagesTo($images, $gallery)
    {
        return $this->_modeHelper->moveImagesTo($images, $gallery);
    }

    /**
     * Copy image and related data to specified gallery.
     *
     * @param array $images           An array of image ids.
     * @param Ansel_Gallery $gallery  The gallery to copy images to.
     *
     * @return integer The number of images copied
     * @throws Ansel_Exception
     */
    public function copyImagesTo($images, $gallery)
    {
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied(sprintf(_("Access denied copying photos to \"%s\"."), $gallery->get('name')));
        }

        $imgCnt = 0;
        foreach ($images as $imageId) {
            $img = $this->getImage($imageId);
            // Note that we don't pass the tags when adding the image..see below
            $newId = $gallery->addImage(array(
                               'image_caption' => $img->caption,
                               'data' => $img->raw(),
                               'image_filename' => $img->filename,
                               'image_type' => $img->getType(),
                               'image_uploaded_date' => $img->uploaded));
            /* Copy any tags */
            $tags = $img->getTags();
            $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($newId, $tags, $gallery->get('owner'), 'image');

            /* exif data */
            // First check to see if the exif data was present in the raw data.
            $count = $GLOBALS['ansel_db']->queryOne('SELECT COUNT(image_id) FROM ansel_image_attributes WHERE image_id = ' . (int) $newId . ';');
            if ($count == 0) {
                $exif = $GLOBALS['ansel_db']->queryAll('SELECT attr_name, attr_value FROM ansel_image_attributes WHERE image_id = ' . (int) $imageId . ';',null, MDB2_FETCHMODE_ASSOC);
                if (is_array($exif) && count($exif) > 0) {
                    $insert = $GLOBALS['ansel_db']->prepare('INSERT INTO ansel_image_attributes (image_id, attr_name, attr_value) VALUES (?, ?, ?)');
                    if ($insert instanceof PEAR_Error) {
                        throw new Horde_Exception($insert->getMessage());
                    }
                    foreach ($exif as $attr){
                        $result = $insert->execute(array($newId, $attr['attr_name'], $attr['attr_value']));
                        if ($result instanceof PEAR_Error) {
                            throw new Horde_Exception($result->getMessage());
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
    public function setImageOrder($imageId, $pos)
    {
        return $GLOBALS['ansel_db']->exec('UPDATE ansel_images SET image_sort = ' . (int)$pos . ' WHERE image_id = ' . (int)$imageId);
    }

    /**
     * Remove the given image from this gallery.
     *
     * @param mixed   $image   Image to delete. Can be an Ansel_Image
     *                         or an image ID.
     *
     * @return boolean  True on success, false on failure.
     */
    public function removeImage($image, $isStack = false)
    {
        return $this->_modeHelper->removeImage($image, $isStack);
    }

    /**
     * Returns this share's owner's Identity object.
     *
     * @return Horde_Prefs_Identity object for the owner of this gallery.
     */
    public function getIdentity()
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($this->data['share_owner']);
    }

    /**
     * Output the HTML for this gallery's tile.
     *
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery object
     * @param Ansel_Style $style     A style object to use.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param array $params          Any additional parameters the Ansel_Tile
     *                               object may need.
     */
    public function getTile($parent = null, $style = null, $mini = false,
                     $params = array())
    {
        if (!is_null($parent) && is_null($style)) {
            $style = $parent->getStyle();
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
    public function getGalleryChildren($perm = Horde_Perms::SHOW, $from = 0, $to = 0, $noauto = true)
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
    public function countGalleryChildren($perm = Horde_Perms::SHOW, $galleries_only = false, $noauto = true)
    {
        return $this->_modeHelper->countGalleryChildren($perm, $galleries_only, $noauto);
    }

    /**
     * Lists a slice of the image ids in this gallery.
     *
     * @param integer $from  The image to start listing.
     * @param integer $count The numer of images to list.
     *
     * @return array  An array of image_ids
     */
    public function listImages($from = 0, $count = 0)
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
    public function getImages($from = 0, $count = 0)
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
    public function getRecentImages($limit = 10)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Storage')->getRecentImages(array($this->id),
                                                          $limit);
    }

    /**
     * Returns the image in this gallery corresponding to the given id.
     *
     * @param integer $id  The ID of the image to retrieve.
     *
     * @return Ansel_Image  The image object corresponding to the given id.
     */
    public function &getImage($id)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($id);
    }

    /**
     * Checks if the gallery has any subgallery
     */
    public function hasSubGalleries()
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
    public function countImages($subgalleries = false)
    {
        return $this->_modeHelper->countImages($subgalleries);
    }

    /**
     * Returns the key image for this gallery.
     *
     * @param Ansel_Style $style  Force the use of this style, if it's available
     *                            otherwise use whatever style is choosen for
     *                            this gallery. If prettythumbs are not
     *                            available then we always use ansel_default
     *                            style.
     *
     * @return mixed  The image_id of the key image or false.
     */
    public function getKeyImage($style = null)
    {
        if (is_null($style)) {
            $style = $this->getStyle();
        }

        if ($style->keyimage_type != 'Thumb') {
            $thumbstyle = $style->keyimage_type;
            $styleHash = $style->getHash($thumbstyle);

            /* First check for the existence of a key image in the specified style */
            if (!empty($this->data['attribute_default_prettythumb'])) {
                $thumbs = @unserialize($this->data['attribute_default_prettythumb']);
            }
            if (!isset($thumbs) || !is_array($thumbs)) {
                $thumbs = array();
            }
            if (!empty($thumbs[$styleHash])) {
                return $thumbs[$styleHash];
            }

            /* Don't already have one, must generate it. */
            //@TODO: Look at passing style both in params and the property...
            $params = array('gallery' => $this, 'style' => $style);
            try {
                $iview = Ansel_ImageGenerator::factory($style->keyimage_type, $params);
                $img = $iview->create();

                // Note the gallery_id is negative for generated stacks
                $iparams = array('image_filename' => $this->get('name'),
                                 'image_caption' => $this->get('name'),
                                 'data' => $img->raw(),
                                 'image_sort' => 0,
                                 'gallery_id' => -$this->id);
                $newImg = new Ansel_Image($iparams);
                $newImg->save();
                $prettyData = serialize(array_merge($thumbs, array($styleHash => $newImg->id)));
                $this->set('default_prettythumb', $prettyData, true);

                return $newImg->id;
            } catch (Horde_Exception $e) {
                // Might not support the requested style...try ansel_default
                // but protect against infinite recursion.
                Horde::logMessage($e->getMessage(), 'DEBUG');
                if ($style->keyimage_type != 'plain') {
                    return $this->getKeyImage(Ansel::getStyleDefinition('ansel_default'));
                }
            }
        } else {
            /* We are just using an image thumbnail. */
            if ($this->countImages()) {
                if (!empty($this->data['attribute_default'])) {
                    return $this->data['attribute_default'];
                }
                $keys = $this->listImages();
                $this->data['attribute_default'] = $keys[count($keys) - 1];
                $this->data['attribute_default_type'] = 'auto';
                $this->save();
                return $keys[count($keys) - 1];
            }

            if ($this->hasSubGalleries()) {
                /* Fall through to a key image of a sub gallery. */
                try {
                    $galleries = $GLOBALS['injector']
                        ->getInstance('Ansel_Storage')
                        ->listGalleries(array('parent' => $this, 'all_levels' => false));

                    foreach ($galleries as $galleryId => $gallery) {
                        if ($default_img = $gallery->getKeyImage($style)) {
                            return $default_img;
                        }
                    }
                } catch (Horde_Exception $e) {
                    return false;
                }
            }
        }

        /* Could not find a key image */
        return false;
    }

    /**
     * Returns this gallery's tags.
     *
     * @return array of tag info
     * @throws Horde_Exception
     */
    public function getTags() {
        if ($this->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            return $GLOBALS['injector']->getInstance('Ansel_Tagger')->getTags($this->id, 'gallery');
        } else {
            throw new Horde_Exception(_("Access denied viewing this gallery."));
        }
    }

    /**
     * Set/replace this gallery's tags.
     *
     * @param array $tags  An array of tag names to associate with this image.
     *
     * @return true on success
     * @throws Horde_Exception
     */
    public function setTags($tags)
    {
        if ($this->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            return $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($this->id, $tags, $this->get('owner'), 'gallery');
        } else {
            throw new Horde_Exception(_("Access denied adding tags to this gallery."));
        }
    }

    /**
     * Return the style definition for this gallery.
     *
     * @return array  The style definition array.
     */
    public function getStyle()
    {
        // No styles allowed per admin.
        if (!$GLOBALS['conf']['image']['prettythumbs']) {
            return Ansel::getStyleDefinition('ansel_default');
        }

        if (empty($this->data['attribute_style'])) {
            // No style configured, use user's prefered default
            $style = Ansel::getStyleDefinition($GLOBALS['prefs']->getValue('default_gallerystyle'));
        } else {
            // Explicitly defined style
            $style = @unserialize($this->data['attribute_style']);
            if (!$style) {
                $style = Ansel::getStyleDefinition($GLOBALS['prefs']->getValue('default_gallerystyle'));
            }
        }

        // Check browser requirements. If we require PNG support, and do not
        // have it, revert to the basic ansel_default style.
        if ($style->requiresPng() &&
            ($GLOBALS['browser']->hasQuirk('png_transparency') ||
             $GLOBALS['conf']['image']['type'] != 'png')) {

            return Ansel::getStyleDefinition('ansel_default');
        }

        return $style;
    }

    /**
     * Return a hash key for the given view and style.
     *
     * @param string $view         The view (thumb, prettythumb etc...)
     * @param Ansel_Style $style   The style.
     *
     * @return string  A md5 hash suitable for use as a key.
     */
    public function getViewHash($view, $style = null)
    {
        if (empty($style)) {
            $style = $this->getStyle();
        }

        return Ansel::getViewHash($view, $style);
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
    public function hasPermission($userid, $permission, $creator = null)
    {
        if ($userid == $this->data['share_owner'] ||
            $GLOBALS['registry']->isAdmin(array('permission' => 'ansel:admin'))) {

            return true;
        }

        return parent::hasPermission($userid, $permission, $creator);
    }

    /**
     * Check user age limtation
     *
     * @return boolean
     */
    public function isOldEnough()
    {
        global $session;

        if (($GLOBALS['registry']->getAuth() &&
             $this->data['share_owner'] == $GLOBALS['registry']->getAuth()) ||
            empty($GLOBALS['conf']['ages']['limits']) ||
            empty($this->data['attribute_age'])) {

            return true;
        }

        // Do we have the user age already cheked?
        if (!$session->exists('ansel', 'user_age')) {
            $session->set('ansel', 'user_age', 0);
            $user_age = 0;
        } else {
            $user_age = $session->get('ansel', 'user_age');
            if ($user_age >= $this->data['attribute_age']) {
                return true;
            }
        }

        // Can we hook user's age?
        if ($GLOBALS['conf']['ages']['hook'] &&
            $GLOBALS['registry']->isAuthenticated()) {
            $result = Horde::callHook('_ansel_hook_user_age');
            if (is_int($result)) {
                $session->set('ansel', 'user_age', $result);
                $user_age = $result;
            }
        }

        return ($user_age >= $this->data['attribute_age']);
    }

    /**
     * Determine if we need to unlock a password protected gallery
     *
     * @return boolean
     */
    public function hasPasswd()
    {
        if ($GLOBALS['registry']->getAuth() &&
            ($GLOBALS['registry']->getAuth() == $this->get('owner') ||
             $GLOBALS['registry']->isAdmin(array('permission' => 'ansel:admin')))) {
            return false;
        }

        $passwd = $this->get('passwd');
        if (empty($passwd)) {
            return false;
        } elseif ($GLOBALS['session']->get('ansel', 'passwd/' . $this->id)) {
            $GLOBALS['session']->set('ansel', 'passwd/' . $this->id, hash('md5', $this->get('passwd')));
            return false;
        }

        return true;
    }

    /**
     * Sets this gallery's parent gallery.
     *
     * @param mixed $parent  An Ansel_Gallery or a gallery_id.
     *
     * @return boolean true on sucess
     * @throws Horde_Exception
     */
    public function setParent($parent)
    {
        /* Make sure we have a gallery object */
        if (!is_null($parent) && !($parent instanceof Ansel_Gallery)) {
            $parent = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($parent);
        }

        /* Check this now since we don't know if we are updating the DB or not */
        $old = $this->getParent();
        $reset_has_subgalleries = false;
        if (!is_null($old)) {
            $vMode = $old->get('view_mode');
            if ($vMode != 'Normal') {
                $old->set('view_mode', 'Normal');
            }
            $cnt = $old->countGalleryChildren(Horde_Perms::READ, true);
            if ($vMode != 'Normal') {
                $old->set('view_mode', $vMode);
            }
            if ($cnt == 1) {
                /* Count is 1, and we are about to delete it */
                $reset_has_subgalleries = true;
                if (!$old->countImages()) {
                    $old->resetKeyImage();
                }
            }
        }

        /* Call the parent class method */
        parent::setParent($parent);

        /* Tell the parent the good news */
        if (!is_null($parent) && !$parent->get('has_subgalleries')) {
            return $parent->set('has_subgalleries', '1', true);
        }
        Horde::logMessage('Ansel_Gallery parent successfully set', 'DEBUG');

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
    public function set($attribute, $value, $update = false)
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

        /* Need to serialize the style object */
        if ($driver_key == 'attribute_style') {
            $value = serialize($value);
        }

        $this->data[$driver_key] = $value;

        /* Update the backend, but only this current change */
        if ($update) {
            $db = $this->getShareOb()->getStorage();
            // Manually convert the charset since we're not going through save()
            $data = $this->getshareOb()->toDriverCharset(array($driver_key => $value));
            $sql = 'UPDATE ' . $this->getShareOb()->getTable() . ' SET ' . $driver_key . ' = ? WHERE share_id = ?';
            if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $this->id);
            }
           $db->update($sql, array($data[$driver_key], $this->id));
        }

        return true;
    }

    public function setDate($date)
    {
        $this->_modeHelper->setDate($date);
    }

    public function getDate()
    {
        return $this->_modeHelper->getDate();
    }

    /**
     * Get an array describing where this gallery is in a breadcrumb trail.
     *
     * @return  An array of 'title' and 'navdata' hashes with the [0] element
     *          being the deepest part.
     */
    public function getGalleryCrumbData()
    {
        return $this->_modeHelper->getGalleryCrumbData();
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        $data = array(
            self::VERSION,
            $this->data,
            $this->_shareCallback,
        );

        return serialize($data);
    }

    public function unserialize($data)
    {
        parent::unserialize($data);
        $this->_setModeHelper($this->get('view_mode'));
    }
}
