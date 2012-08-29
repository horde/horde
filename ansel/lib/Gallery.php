<?php
/**
 * Class to encapsulate a single gallery. Implemented as an extension of
 * the Horde_Share_Object class.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Gallery implements Serializable
{
    /**
     * Serializable version constant
     */
    const VERSION = 3;

    /**
     * The share object for this gallery.
     *
     * @var Horde_Share_Object
     */
    protected $_share;

    /**
     * The gallery mode helper
     *
     * @var Ansel_GalleryMode_* object
     */
    protected $_modeHelper;

    /**
     * The Ansel_Gallery constructor.
     *
     * @param Horde_Share_Object  The share representing this gallery.
     *
     * @return Ansel_Gallery
     */
    public function __construct(Horde_Share_Object $share)
    {
        $this->_share = $share;

        $this->_setModeHelper(
            $share->get('view_mode') ? $share->get('view_mode') : 'Normal');
    }

    /**
     * Helper for accessing the gallery id
     *
     * @param string $property The property
     *
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property) {
        case 'id':
            return $this->_share->getId();
        default:
            return null;
        }
    }

    /**
     * Get a gallery property
     *
     * @param string $property  The property to return.
     *
     * @return mixed  The value.
     */
    public function get($property)
    {
        $value = $this->_share->get($property);
        if ($property == 'style') {
            $value = unserialize($value);
        }

        return $value;
    }

    /**
     *
     * @return array  An array of Ansel_Gallery objects.
     */
    public function getParents()
    {
        $p = $this->_share->getParents();
        if (!empty($p)) {
            return $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->buildGalleries($this->_share->getParents());
        } else {
            return array();
        }
    }

    /**
     *
     * @return Ansel_Gallery
     */
    public function getParent()
    {
        $p = $this->_share->getParent();
        if (!empty($p)) {
            return $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->buildGallery($this->_share->getParent());
        } else {
            return null;
        }
    }

    public function setPermission(Horde_Perms_Permision$permission, $update = true)
    {
        $this->_share->setPermission($permission, $update);
    }

    /**
     * Get the gallery's share object.
     *
     * @return Horde_Share_Object
     */
    public function getShare()
    {
        return $this->_share;
    }

    /**
     * Check for special capabilities of this gallery.
     *
     * @param string $feature  The feature to check for.
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
     * @TODO: Use DI
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
            ($GLOBALS['registry']->getAuth() == $this->get('owner') ||
             $GLOBALS['registry']->isAdmin(array('permission' => 'ansel:admin')))) {
            return true;
        }

        switch ($this->_share->get('download')) {
        case 'all':
            return true;

        case 'authenticated':
            return $GLOBALS['registry']->isAuthenticated();

        case 'edit':
            return $this->_share->hasPermission(
                $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);

        case 'hook':
            try {
                return Horde::callHook('can_download', array($this->id));
            } catch (Horde_Exception_HookNotSet $e) {}

        default:
            return false;
        }
    }

    /**
     * Saves any changes to this object to the backend permanently.
     *
     * @throws Ansel_Exception
     */
    public function save()
    {
        // Check for invalid characters in the slug.
        $slug = $this->get('slug');
        if ($slug && preg_match('/[^a-zA-Z0-9_@]/', $slug)) {
            throw new InvalidArgumentException(
                sprintf(_("Could not save gallery, the slug, \"%s\", contains invalid characters."),
                        $slug));
        }

        // Check for slug uniqueness
        if (!empty($this->_oldSlug) && $slug != $this->_oldSlug) {
            if ($GLOBALS['injector']->getInstance('Ansel_Storage')->galleryExists(null, $slug)) {
                throw InvalidArgumentException(
                    sprintf(_("Could not save gallery, the slug, \"%s\", already exists."),
                            $slug));
            }
        }

        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')
                ->expire('Ansel_Gallery' . $this->id);
        }

        try {
            $this->_share->save();
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Update the gallery image count.
     *
     * @param integer $images      Number of images in action
     * @param boolean $add         True if adding, false if removing
     *
     * @throws Ansel_Exception
     */
    public function updateImageCount($images, $add = true)
    {
        /* Updating self */
        if ($add) {
            $this->set('images',  $this->get('images') + $images);
        } else {
            $this->set('images',  $this->_share->get('images') - $images);
        }
        $this->save();

        /* Make sure we get rid of key image/stacks if no more images */
        if (!$this->get('images')) {
            $this->resetKeyImage();
        }

        /* Need to expire the cache for the gallery that was changed */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')
                ->expire('Ansel_Gallery' . $this->id);
        }
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
        // Normal is the only view mode that can accurately update counts
        $vMode = $this->get('view_mode');
        if ($vMode != 'Normal') {
            $this->_setModeHelper('Normal');
        }
        // Make sure it's taken as a new image
        $image->id = null;
        $image->gallery = $this->id;
        $image->sort = $this->countImages();
        $image->save();
        $this->updateImageCount(1);

        // Should this be the key image?
        if ($default) {
            $this->set('default', $image->id);
            $this->clearStacks();
        }

        /* Save all changes to the gallery */
        $this->save();

        // Return to the proper view mode
        if ($vMode != 'Normal') {
            $this->_setModeHelper($vMode);
        }

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
    public function addImage(array $image_data, $default = false)
    {
        global $conf;

        /* Normal is the only view mode that can accurately update counts */
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
            $params['name'] = $image->getImagePageCount() . ' page image: '
                . $image->filename;
            $mGallery = $GLOBALS['injector']->getInstance('Ansel_Storage')
                ->createGallery($params, $this->_share->getPermission(), $this);
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
        if (!$default && $this->get('default_type') == 'auto') {
            $this->set('default', $image->id);
            $resetStack = true;
        } elseif ($default) {
            $this->set('default', $image->id);
            $this->set('type', 'manual');
        }

        /* Reset the gallery key image stacks if needed. */
        if ($resetStack) {
            $this->clearStacks();
        }

        /* Update the modified flag and save gallery changes */
        $this->set('last_modified', time());

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
     * @throws Ansel_Exception
     */
    public function clearStacks()
    {
        $ids = @unserialize($this->get('default_prettythumb'));
        if (is_array($ids)) {
            try {
                foreach ($ids as $imageId) {
                    $this->removeImage($imageId, true);
                }
            } catch (Horde_Exception_NotFound $e) {
                throw new Ansel_Exception($e);
            }
        }

        // Using the set function here so we can efficently update the db
        $this->set('default_prettythumb', '', true);
    }

    /**
     * Removes all generated and cached thumbnails for this gallery.
     */
    public function clearThumbs()
    {
        $images = $this->listImages();
        foreach ($images as $id) {
            $image = $this->getImage($id);
            $image->deleteCache('thumb');
        }
    }

    /**
     * Removes all generated and cached views for this gallery.
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
     * @return integer  The number of images moved.
     */
    public function moveImagesTo(array $images, Ansel_Gallery $gallery)
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
    public function copyImagesTo(array $images, Ansel_Gallery $gallery)
    {
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied(
                sprintf(_("Access denied copying photos to \"%s\"."),
                        $gallery->get('name')));
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
            $GLOBALS['injector']->getInstance('Ansel_Tagger')
                ->tag($newId, $tags, $gallery->get('owner'), 'image');

            // Check that new image_id doesn't have existing attributes,
            // throw exception if it does.
            $newAttributes = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getImageAttributes($newId);
            if (count($newAttributes)) {
                throw new Ansel_Exception(_("Image already has existing attributes."));
            }

            $exif = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getImageAttributes($imageId);

            if (is_array($exif) && count($exif) > 0) {
                foreach ($exif as $name => $value){
                    $GLOBALS['injector']
                        ->getInstance('Ansel_Storage')
                        ->saveImageAttribute($newId, $name, $value);
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
     *
     * @throws Ansel_Exception
     */
    public function setImageOrder($imageId, $pos)
    {
        $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->setImageSortOrder($imageId, $pos);
    }

    /**
     * Remove the given image from this gallery.
     *
     * @param mixed $image   Image to delete. Can be an Ansel_Image
     *                       or an image ID.
     *
     * @param boolean $isStack  Indicates if this image represents a stack image.
     * @throws Horde_Exception_NotFound, Ansel_Exception
     */
    public function removeImage($image, $isStack = false)
    {
        $this->_modeHelper->removeImage($image, $isStack);
    }

    /**
     * Returns this share's owner's Identity object.
     *
     * @return Horde_Prefs_Identity object for the owner of this gallery.
     */
    public function getIdentity()
    {
        return $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create($this->get('owner'));
    }

    /**
     * Output the HTML for this gallery's tile.
     *
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery object
     * @param Ansel_Style $style     A style object to use.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param array $params          Any additional parameters the Ansel_Tile
     *                               object may need.
     *
     * @return Ansel_Tile_Gallery
     */
    public function getTile(Ansel_Gallery $parent = null,
                            Ansel_Style $style = null,
                            $mini = false,
                            array $params = array())
    {
        if (!is_null($parent) && is_null($style)) {
            $style = $parent->getStyle();
        }

        return Ansel_Tile_Gallery::getTile($this, $style, $mini, $params);
    }

    /**
     * Get all children of this share.
     *
     * @param string $user        The user to use for checking perms
     * @param integer $perm       Horde_Perms::* constant. If NULL will return
     *                            all shares regardless of permissions.
     * @param boolean $allLevels  Return all levels.
     *
     * @return array  An array of Ansel_Gallery objects
     * @throws Ansel_Exception
     */
    public function getChildren($user, $perm = Horde_Perms::SHOW, $allLevels = true)
    {
        try {
            return $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->buildGalleries($this->_share->getChildren($user, $perm, $allLevels));
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }
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
    public function getGalleryChildren($perm = Horde_Perms::SHOW,
                                       $from = 0,
                                       $to = 0,
                                       $noauto = true)
    {
        return $this->_modeHelper->getGalleryChildren($perm, $from, $to, $noauto);
    }

    /**
     * Return the count of this gallery's children
     *
     * @param integer $perm            The permissions to require.
     * @param boolean $galleries_only  Only include galleries, no images.
     * @param boolean $noauto          Do not auto drill down into gallery tree.
     *
     * @return integer The count of this gallery's children.
     */
    public function countGalleryChildren($perm = Horde_Perms::SHOW,
                                         $galleries_only = false,
                                         $noauto = true)
    {
        return $this->_modeHelper->countGalleryChildren(
            $perm, $galleries_only, $noauto);
    }

    public function countChildren($user, $perm = Horde_Perms::SHOW, $allLevels = true)
    {
        return $this->_share->CountChildren($user, $perm, $allLevels);
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
     * @param array  An array of Ansel_Image objects
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
     * @return array  An array of Ansel_Image objects
     */
    public function getRecentImages($limit = 10)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Storage')
            ->getRecentImages(array($this->id), $limit);
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
     *
     * @return boolean
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
    public function getKeyImage(Ansel_Style $style = null)
    {
        if (is_null($style)) {
            $style = $this->getStyle();
        }

        if ($style->keyimage_type != 'Thumb') {
            $thumbstyle = $style->keyimage_type;
            $styleHash = $style->getHash($thumbstyle);

            // First check for the existence of a key image in the specified style
            if ($this->get('default_prettythumb')) {
                $thumbs = @unserialize($this->get('default_prettythumb'));
            }
            if (!isset($thumbs) || !is_array($thumbs)) {
                $thumbs = array();
            }
            if (!empty($thumbs[$styleHash])) {
                return $thumbs[$styleHash];
            }

            // Don't already have one, must generate it.
            $params = array('gallery' => $this, 'style' => $style);
            try {
                $iview = Ansel_ImageGenerator::factory($style->keyimage_type, $params);
                $img = $iview->create();

                // Note the gallery_id is negative for generated stacks
                $iparams = array(
                    'image_filename' => $this->get('name'),
                    'image_caption' => $this->get('name'),
                    'data' => $img->raw(),
                    'image_sort' => 0,
                    'gallery_id' => -$this->id);
                $newImg = new Ansel_Image($iparams);
                $newImg->save();
                $prettyData = serialize(
                    array_merge($thumbs, array($styleHash => $newImg->id)));
                $this->set('default_prettythumb', $prettyData, true);

                // Make sure the hash is saved since it might be different then
                // the gallery's
                $GLOBALS['injector']
                    ->getInstance('Ansel_Storage')
                    ->ensureHash($styleHash);

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
            // We are just using an image thumbnail.
            if ($this->countImages()) {
                if ($default = $this->get('default')) {
                    return $default;
                }
                $keys = $this->listImages();
                $this->set('default', $keys[count($keys) - 1]);
                $this->set('default_type', 'auto');
                $this->save();
                return $keys[count($keys) - 1];
            }

            if ($this->hasSubGalleries()) {
                // Fall through to a key image of a sub gallery.
                try {
                    $galleries = $GLOBALS['injector']
                        ->getInstance('Ansel_Storage')
                        ->listGalleries(array('parent' => $this->id, 'all_levels' => false));

                    foreach ($galleries as $gallery) {
                        if ($default_img = $gallery->getKeyImage($style)) {
                            return $default_img;
                        }
                    }
                } catch (Horde_Exception $e) {
                    return false;
                }
            }
        }

        // Could not find a key image
        return false;
    }

    /**
     * Returns this gallery's tags.
     *
     * @return array of tag info
     * @throws Horde_Exception
     */
    public function getTags()
    {
        if ($this->_share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            return $GLOBALS['injector']->getInstance('Ansel_Tagger')
                ->getTags($this->id, 'gallery');
        } else {
            throw new Horde_Exception(_("Access denied viewing this gallery."));
        }
    }

    /**
     * Set/replace this gallery's tags.
     *
     * @param array $tags  An array of tag names to associate with this image.
     *
     * @throws Horde_Exception_PermissionDenied
     */
    public function setTags(array $tags, $replace = true)
    {
        if ($this->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {

            if ($replace) {
                $GLOBALS['injector']
                    ->getInstance('Ansel_Tagger')
                    ->replaceTags(
                        $this->id,
                        $tags,
                        $this->get('owner'),
                        'gallery');
            } else {
                $GLOBALS['injector']
                    ->getInstance('Ansel_Tagger')
                    ->tag(
                        $this->id,
                        $tags,
                        $this->get('owner'),
                        'gallery');
            }
        } else {
            throw new Horde_Exception_PermissionDenied(_("Access denied adding tags to this gallery."));
        }
    }

    /**
     * Remove a single tag from this gallery's tag collection
     *
     * @param string $tag  The tag name to remove.
     */
    public function removeTag($tag)
    {
        if ($this->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $GLOBALS['injector']
                ->getInstance('Ansel_Tagger')
                ->untag(
                    (string)$this->id,
                    $tag,
                    'gallery');
        }
    }

    /**
     * Return the style definition for this gallery.
     *
     * @return Ansel_Style  The style definition array.
     */
    public function getStyle()
    {
        // No styles allowed per admin.
        if (!$GLOBALS['conf']['image']['prettythumbs']) {
            return Ansel::getStyleDefinition('ansel_default');
        }

        if (!$this->get('style')) {
            // No style configured, use user's prefered default
            $style = Ansel::getStyleDefinition(
                $GLOBALS['prefs']->getValue('default_gallerystyle'));
        } else {
            // Explicitly defined style
            $style = $this->get('style');
        }

        // Check browser requirements. If we require PNG support, and do not
        // have it, revert to the basic ansel_default style.
        if ($style->requiresPng() &&
            $GLOBALS['conf']['image']['type'] != 'png') {
            $style = Ansel::getStyleDefinition('ansel_default');
        }

        return $style;
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
        if ($userid == $this->get('owner') ||
            $GLOBALS['registry']->isAdmin(array('permission' => 'ansel:admin'))) {

            return true;
        }

        return $this->_share->hasPermission($userid, $permission, $creator);
    }

    /**
     * Returns the permission of this share.
     *
     * @return Horde_Perms_Permission  Permission object that represents the
     *                                 permissions on this share.
     */
    public function getPermission()
    {
        return $this->_share->getPermission();
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
             $this->get('owner') == $GLOBALS['registry']->getAuth()) ||
            empty($GLOBALS['conf']['ages']['limits']) ||
            !$this->get('age')) {

            return true;
        }

        // Do we have the user age already cheked?
        if (!$session->exists('ansel', 'user_age')) {
            $session->set('ansel', 'user_age', 0);
            $user_age = 0;
        } else {
            $user_age = $session->get('ansel', 'user_age');
            if ($user_age >= $this->get('age')) {
                return true;
            }
        }

        // Can we hook user's age?
        if ($GLOBALS['conf']['ages']['hook'] &&
            $GLOBALS['registry']->isAuthenticated()) {
            try {
                $result = Horde::callHook('user_age', array(), 'ansel');
            } catch (Horde_Exception_HookNotSet $e) {}
            if (is_int($result)) {
                $session->set('ansel', 'user_age', $result);
                $user_age = $result;
            }
        }

        return ($user_age >= $this->get('age'));
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
     * @throws Ansel_Exception
     */
    public function setParent($parent)
    {
        /* Make sure we have a gallery object */
        if (!is_null($parent) && !($parent instanceof Ansel_Gallery)) {
            $parent = $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->getGallery($parent);
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
        try {
            $this->_share->setParent(!is_null($parent) ? $parent->getShare() : null);
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }
        /* Tell the parent the good news */
        if (!is_null($parent) && !$parent->get('has_subgalleries')) {
            $parent->set('has_subgalleries', '1', true);
        }
        Horde::logMessage('Ansel_Gallery parent successfully set', 'DEBUG');

       /* Gallery parent changed, safe to change the parent's attributes */
       if ($reset_has_subgalleries) {
           $old->set('has_subgalleries', 0, true);
       }
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     * @param boolean $update    Commit only this change to storage.
     *
     * @throws Ansel_Exception
     */
    public function set($attribute, $value, $update = false)
    {
        if ($attribute == 'slug') {
            $this->_oldSlug = $this->get('slug');
        }

        /* Need to serialize the style object */
        if ($attribute == 'style') {
            $value = serialize($value);
        }

        if ($attribute == 'view_mode' && $this->get('view_mode') != $value) {
            //$mode = isset($attributes['attribute_view_mode']) ? $attributes['attribute_view_mode'] : 'Normal';
            $this->_setModeHelper($value);
        }

        try {
            $this->_share->set($attribute, $value, $update);
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }
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
            $this->_share
        );

        return serialize($data);
    }

    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }
        $this->_share = $data[1];
        $this->_setModeHelper($this->get('view_mode'));
    }

    /**
     * Returns a json representation of this gallery.
     *
     * @param boolean $full  Return all information (subgalleries and images)?
     *
     * @return StdClass  An object describing the gallery
     * <pre>
     * 'id' - gallery id
     * 'p'  - gallery's parent's id (null if top level)
     * 'pn' - gallery's parent's name (null if top level)
     * 'n'  - gallery name
     * 'dc' - date created
     * 'dm' - date modified
     * 'd'  - description
     * 'ki' - key image
     * 'sg' - an object with the following properties:
     *      'n'  - gallery name
     *      'dc' - date created
     *      'dm' - date modified
     *      'd'  - description
     *      'ki' - key image
     *
     *  'imgs' - an array of image objects with the following properties:
     *      'id'  - the image id
     *      'url' - the image url
     * </pre>
     */
    public function toJson($full = false)
    {
        // @TODO: Support date grouped galleries
        $vMode = $this->get('view_mode');
        if ($vMode != 'Normal') {
            $this->_setModeHelper('Normal');
        }
        $style = Ansel::getStyleDefinition('ansel_mobile');

        $json = new StdClass();
        $json->id = $this->id;
        $json->n = $this->get('name');
        $json->dc = $this->get('date_created');
        $json->dm = $this->get('last_modified');
        $json->d = $this->get('desc');
        $json->ki = Ansel::getImageUrl($this->getKeyImage($style), 'thumb', false, $style)->toString(true);
        $json->imgs = array();

        // Parent
        $parents = $this->getParents();
        if (empty($parents)) {
            $json->p = null;
            $json->pn = null;
        } else {
            $p = array_pop($parents);
            $json->p =$p->id;
            $json->pn = $p->get('name');
        }

        if ($full) {
            $json->tiny = ($GLOBALS['conf']['image']['tiny'] &&
                           ($GLOBALS['conf']['vfs']['src'] == 'direct' || $this->_share->hasPermission('', Horde_Perms::READ)));
            $json->sg = array();
            if ($this->hasSubGalleries()) {
                $sgs = $this->getChildren(
                    $GLOBALS['registry']->getAuth(),
                    Horde_Perms::READ,
                    false);//GLOBALS['injector']->getInstance('Ansel_Storage')->listGalleries(array('parent' => $this->id, 'all_levels' => false));
                foreach ($sgs as $g) {
                    $json->sg[] = $g->toJson();
                }
            }

            $images = $this->getImages();
            foreach ($images as $img) {
                $i = new StdClass();
                $i->id = $img->id;
                $i->url = Ansel::getImageUrl($img->id, 'thumb', false, $style)->toString(true);
                $i->screen = Ansel::getImageUrl($img->id, 'screen', $json->tiny, Ansel::getStyleDefinition('ansel_default'))->toString(true);
                $i->fn = $img->filename;
                $json->imgs[] = $i;
            }
        }

        if ($vMode != 'Normal') {
            $this->_setModeHelper($vMode);
        }
        return $json;
    }

    public function toArray()
    {
        $fields = array(
            'date_created', 'last_modified', 'owner', 'name', 'desc', 'default',
             'default_type', 'default_prettythumb', 'images', 'has_subgalleries',
             'slug', 'age', 'download', 'passwd', 'faces', 'view_mode');
        $gallery = array();
        foreach ($fields as $field) {
            $gallery[$field] = $this->get($field);
        }
        $gallery['id'] = $this->id;

        return $gallery;
    }

}
