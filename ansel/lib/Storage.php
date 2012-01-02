<?php
/**
 * Class for interfacing with back end data storage.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Storage
{
    /**
     * database handle
     *
     * @var Horde_Db_Adapter
     */
    private $_db;

    /**
     * The Horde_Shares object to use for this scope.
     *
     * @var Horde_Share
     */
    private $_shares;

    /**
     * Local cache of retrieved images
     *
     * @var array
     */
    private $_images = array();

    /**
     * Const'r
     *
     * @param Horde_Core_Share_Driver  The share object
     *
     * @return Ansel_Storage
     */
    public function __construct(Horde_Core_Share_Driver $shareOb)
    {
        $this->_shares = $shareOb;
    }

    /**
     * Property accessor
     *
     * @param string $property  The property to access.
     */
    public function __get($property)
    {
        switch ($property) {
        case 'shares':
            return $this->{'_' . $property};
        default: // Just for now until everything is refactored.
            return null;
        }
    }

    /**
     * Backend setter
     *
     * @param mixed  $storage  The backend storage driver.
     */
    public function setStorage($storage)
    {
        $this->_db = $storage;
    }

   /**
    * Create and initialise a new gallery object.
    *
    * @param array $attributes             The gallery attributes.
    * @param Horde_Perms_Permission $perm  The permissions for the gallery if
    *                                      the defaults are not desirable.
    * @param integer $parent               The id of the parent gallery (if any)
    *
    * @return Ansel_Gallery  A new gallery object.
    * @throws Ansel_Exception
    */
    public function createGallery(array $attributes = array(),
                                  Horde_Perms_Permission $perm = null,
                                  $parent = null)
    {
        // Required values.
        if (empty($attributes['owner'])) {
            $attributes['owner'] = $GLOBALS['registry']->getAuth();
        }
        if (empty($attributes['name'])) {
            $attributes['name'] = _("Unnamed");
        }
        if (empty($attributes['desc'])) {
            $attributes['desc'] = '';
        }

        // Default values
        $attributes['default_type'] = isset($attributes['default_type']) ?
            $attributes['default_type'] :
            'auto';
        $attributes['default'] = isset($attributes['default']) ?
            (int)$attributes['default'] :
            0;
        $attributes['default_prettythumb'] = isset($attributes['default_prettythumb']) ?
            $attributes['default_prettythumb'] :
            '';

        // No value for style now means to use the 'default_ansel' style as
        // defined in styles.php
        $attributes['style'] = isset($attributes['style']) ? $attributes['style'] : '';
        $attributes['date_created'] = time();
        $attributes['last_modified'] = $attributes['date_created'];
        $attributes['images'] = isset($attributes['images']) ?
            (int)$attributes['images'] :
            0;
        $attributes['slug'] = isset($attributes['slug']) ? $attributes['slug'] : '';
        $attributes['age'] = isset($attributes['age']) ? (int)$attributes['age'] : 0;
        $attributes['download'] = isset($attributes['download']) ?
            $attributes['download'] :
            $GLOBALS['prefs']->getValue('default_download');
        $attributes['view_mode'] = isset($attributes['view_mode']) ?
            $attributes['view_mode'] :
            'Normal';
        $attributes['passwd'] = isset($attributes['passwd']) ?
            $attributes['passwd'] :
            '';

        // Don't pass tags to the share creation method.
        if (isset($attributes['tags'])) {
            $tags = $attributes['tags'];
            unset($attributes['tags']);
        } else {
            $tags = array();
        }

        // Check for slug uniqueness
        if (!empty($attributes['slug']) &&
            $this->galleryExists(null, $attributes['slug'])) {
            throw new Horde_Exception(
                sprintf(_("The slug \"%s\" already exists."), $attributes['slug']));
        }

        // Create the gallery's share, and then the gallery.
        try {
            $gallery_share = $this->_shares->newShare(
                $GLOBALS['registry']->getAuth(),
                strval(new Horde_Support_Randomid()),
                $attributes['name']);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage, 'ERR');
            throw new Ansel_Exception($e);
        }
        $gallery = $this->buildGallery($gallery_share);

        // Set the gallery's parent if needed, and clear the parent's cache
        if (!is_null($parent)) {
            $gallery->setParent($parent);
            if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                $GLOBALS['injector']->getInstance('Horde_Cache')
                    ->expire('Ansel_Gallery' . $parent);
            }
        }

        // Fill up the new gallery
        foreach ($attributes as $key => $value) {
            if ($key != 'name') {
                $gallery->set($key, $value);
            }
        }

        // Save it to storage
        try {
            $result = $this->_shares->addShare($gallery_share);
        } catch (Horde_Share_Exception $e) {
            $error = sprintf(_("The gallery \"%s\" could not be created: %s"),
                             $attributes['name'], $e->getMessage());
            Horde::logMessage($error, 'ERR');
            throw new Ansel_Exception($error);
        }

        // Add default permissions.
        if (empty($perm)) {
            $perm = $gallery->getPermission();

            // Default permissions for logged in users
            switch ($GLOBALS['prefs']->getValue('default_permissions')) {
            case 'read':
                $perms = Horde_Perms::SHOW | Horde_Perms::READ;
                break;
            case 'edit':
                $perms = Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT;
                break;
            case 'none':
                $perms = 0;
                break;
            }
            $perm->addDefaultPermission($perms, false);

            // Default guest permissions
            switch ($GLOBALS['prefs']->getValue('guest_permissions')) {
            case 'read':
                $perms = Horde_Perms::SHOW | Horde_Perms::READ;
                break;
            case 'none':
            default:
                $perms = 0;
                break;
            }
            $perm->addGuestPermission($perms, false);

            // Default user groups permissions
            switch ($GLOBALS['prefs']->getValue('group_permissions')) {
            case 'read':
                $perms = Horde_Perms::SHOW | Horde_Perms::READ;
                break;
            case 'edit':
                $perms = Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT;
                break;
            case 'delete':
                $perms = Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT | Horde_Perms::DELETE;
                break;
            case 'none':
            default:
                $perms = 0;
                break;
            }

            if ($perms) {
                $group_list = $GLOBALS['injector']
                    ->getInstance('Horde_Group')
                    ->listGroups($GLOBALS['registry']->getAuth());
                if (count($group_list)) {
                    foreach ($group_list as $group_id => $group_name) {
                        $perm->addGroupPermission($group_id, $perms, false);
                    }
                }
            }
        }
        $gallery->setPermission($perm);

        // Initial tags
        if (count($tags)) {
            $gallery->setTags($tags);
        }

        return $gallery;
    }

    /**
     * Retrieve an Ansel_Gallery given the gallery's slug
     *
     * @param string $slug      The gallery slug
     * @param array $overrides  An array of attributes that should be overridden
     *                          when the gallery is returned.
     *
     * @return Ansel_Gallery The gallery object
     * @throws Horde_Exception_NotFound
     */
    public function getGalleryBySlug($slug, array $overrides = array())
    {
        $shares = $this->buildGalleries(
            $this->_shares->listShares(
                $GLOBALS['registry']->getAuth(),
                array('attributes' => array('slug' => $slug))));
        if (!count($shares)) {
            throw new Horde_Exception_NotFound(sprintf(_("Gallery %s not found."), $slug));
        }

        return current($shares);
     }

    /**
     * Retrieve an Ansel_Gallery given the share id
     *
     * @param integer $gallery_id  The gallery_id to fetch
     * @param array $overrides     An array of attributes that should be
     *                             overridden when the gallery is returned.
     *
     * @return Ansel_Gallery
     * @throws Ansel_Exception
     */
    public function getGallery($gallery_id, array $overrides = array())
    {
        if (!count($overrides) && $GLOBALS['conf']['ansel_cache']['usecache'] &&
            ($gallery = $GLOBALS['injector']->getInstance('Horde_Cache')->get('Ansel_Gallery' . $gallery_id, $GLOBALS['conf']['cache']['default_lifetime'])) !== false) {

            if ($cached_gallery = unserialize($gallery)) {
                return $cached_gallery;
            }
        }

        try {
            $result = $this->buildGallery(
                $this->_shares->getShareById($gallery_id)
            );
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }
        // Don't cache if we have overridden anything
        if (!count($overrides)) {
            if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                $GLOBALS['injector']->getInstance('Horde_Cache')
                    ->set('Ansel_Gallery' . $gallery_id, serialize($result));
            }
        } else {
            foreach ($overrides as $key => $value) {
                $result->set($key, $value, false);
            }
        }

        return $result;
    }

    /**
     * Retrieve an array of Ansel_Gallery objects for the given slugs.
     *
     * @param array $slugs  The gallery slugs.
     *
     * @return array  An array of Ansel_Gallery objects.
     * @throws Ansel_Exception
     */
    public function getGalleriesBySlugs(array $slugs, $perms = Horde_Perms::SHOW)
    {
        try {
            return $this->buildGalleries(
                $this->_shares->listShares(
                    $GLOBALS['registry']->getAuth(),
                    array(
                        'perm' => $perms,
                        'attribtues' => array('slugs' => $slugs))));
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Retrieve an array of Ansel_Gallery objects for the requested ids
     *
     * @param array $ids      Gallery ids to fetch
     * @param integer $perms  Horde_Perms constant for the perms required.
     *
     * @return array  An array of Ansel_Gallery objects
     * @throws Ansel_Exception
     */
    public function getGalleries(array $ids, $perms = Horde_Perms::SHOW)
    {
        try {
            $shares = $this->buildGalleries(
                $this->_shares->getShares($ids));
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }
        $galleries = array();
        foreach ($shares as $gallery) {
            if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), $perms)) {
                $galleries[] = $gallery;
            }
        }

        return $galleries;
    }

    /**
     * Empties a gallery of all images.
     *
     * @param Ansel_Gallery $gallery  The ansel gallery to empty.
     *
     * @throws Ansel_Exception
     */
    public function emptyGallery(Ansel_Gallery $gallery)
    {
        $gallery->clearStacks();
        $images = $gallery->listImages();
        foreach ($images as $image) {
            // Pretend we are a stack so we don't update the images count
            // for every image deletion, since we know the end result will
            // be zero.
            try {
                $gallery->removeImage($image, true);
            } catch (Horde_Exception_NotFound $e) {
                throw new Ansel_Exception($e);
            }
        }
        $gallery->set('images', 0, true);

        // Clear the OtherGalleries widget cache
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']
                ->getInstance('Horde_Cache')
                ->expire('Ansel_OtherGalleries' . $gallery->get('owner'));
            $GLOBALS['injector']
                ->getInstance('Horde_Cache')
                ->expire('Ansel_Gallery' . $gallery->id);
        }
    }

    /**
     * Removes an Ansel_Gallery.
     *
     * @param Ansel_Gallery $gallery  The gallery to delete
     *
     * @throws Ansel_Exception
     */
    public function removeGallery(Ansel_Gallery $gallery)
    {
        // Get any children and empty them
        $children = $gallery->getChildren(null, null, true);
        foreach ($children as $child) {
            $this->emptyGallery($child);
            $child->setTags(array());
        }

        // Now empty the selected gallery of images
        $this->emptyGallery($gallery);

        // Clear all the tags.
        $gallery->setTags(array());

        // Get the parent, if it exists, before we delete the gallery.
        $parent = $gallery->getParent();
        $id = $gallery->id;

        // Delete the gallery from storage
        try {
            $this->_shares->removeShare($gallery->getShare());
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }

        // Expire the cache
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')
                ->expire('Ansel_Gallery' . $id);
        }

        // See if we need to clear the has_subgalleries field
        if ($parent instanceof Ansel_Gallery) {
            if (!$parent->countChildren($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW, false)) {
                $parent->set('has_subgalleries', 0, true);
                if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                    $GLOBALS['injector']
                        ->getInstance('Horde_Cache')
                        ->expire('Ansel_Gallery' . $parent->id);
                }
            }
        }
    }

    /**
     * Returns the image corresponding to the given id.
     *
     * @param integer $id  The image_id of the image to retrieve.
     *
     * @return Ansel_Image  The image object requested..
     * @throws Ansel_Exception, Horde_Exception_NotFound
     */
    public function &getImage($id)
    {
        if (isset($this->_images[$id])) {
            return $this->_images[$id];
        }

        $q = 'SELECT ' . $this->_getImageFields()
            . ' FROM ansel_images WHERE image_id = ?';
        try {
            $image = $this->_db->selectOne($q, array((int)$id));
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }

        if (!$image) {
            throw new Horde_Exception_NotFound(_("Photo not found"));
        } else {
            $image['image_filename'] = Horde_String::convertCharset(
                $image['image_filename'],
                $GLOBALS['conf']['sql']['charset'],
                'UTF-8');
            $image['image_caption'] = Horde_String::convertCharset(
                $image['image_caption'],
                $GLOBALS['conf']['sql']['charset'],
                'UTF-8');
            $this->_images[$id] = new Ansel_Image($image);

            return $this->_images[$id];
        }
    }

    /**
     * Save image details to storage. Does NOT update the cached image files.
     *
     * @param Ansel_Image $image  The image to save.
     *
     * @return integer The image id
     * @throws Ansel_Exception
     */
    public function saveImage(Ansel_Image $image)
    {
        // If we have an id, then it's an existing image.
        if ($image->id) {
            $update = 'UPDATE ansel_images SET image_filename = ?, '
                . 'image_type = ?, image_caption = ?, image_sort = ?, '
                . 'image_original_date = ?, image_latitude = ?, '
                . 'image_longitude = ?, image_location = ?, '
                . 'image_geotag_date = ? WHERE image_id = ?';
            try {
               return $this->_db->update(
                   $update,
                   array(Horde_String::convertCharset($image->filename, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                         $image->type,
                         Horde_String::convertCharset($image->caption, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                         $image->sort,
                         $image->originalDate,
                         $image->lat,
                         $image->lng,
                         $image->location,
                         $image->geotag_timestamp,
                         $image->id));
            } catch (Horde_Db_Exception $e) {
                throw new Ansel_Exception($e);
            }
        }

        // Saving a new Image
        if (!$image->gallery || !strlen($image->filename) || !$image->type) {
            throw new Ansel_Exception('Incomplete photo');
        }

        // Prepare the SQL statement
        $insert = 'INSERT INTO ansel_images (gallery_id, image_filename, '
            . 'image_type, image_caption, image_uploaded_date, image_sort, '
            . 'image_original_date, image_latitude, image_longitude, '
            . 'image_location, image_geotag_date) VALUES '
            . '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        try {
            $image->id = $this->_db->insert(
                $insert,
                array($image->gallery,
                      Horde_String::convertCharset(
                          $image->filename,
                          'UTF-8',
                          $GLOBALS['conf']['sql']['charset']),
                      $image->type,
                      Horde_String::convertCharset(
                          $image->caption,
                          'UTF-8',
                          $GLOBALS['conf']['sql']['charset']),
                      $image->uploaded,
                      $image->sort,
                      $image->originalDate,
                      $image->lat,
                      $image->lng,
                      $image->location,
                     (empty($image->lat) ? 0 : $image->uploaded)));
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }

        return $image->id;
    }

    /**
     * Store an image attribute to storage
     *
     * @param integer $image_id    The image id
     * @param string  $attributes  The attribute name
     * @param string  $value       The attrbute value
     *
     * @throws Ansel_Exception
     */
    public function saveImageAttribute($image_id, $attribute, $value)
    {
        try {
            $this->_db->insert(
                'INSERT INTO ansel_image_attributes '
                . '(image_id, attr_name, attr_value) VALUES (?, ?, ?)',
                array(
                    $image_id,
                    $attribute,
                    Horde_String::convertCharset($value, 'UTF-8', $GLOBALS['conf']['sql']['charset'])));
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Clears an image's attributes from storage.
     *
     * @param integer $image_id  The image to clear
     *
     * @throws Ansel_Exception
     */
    public function clearImageAttributes($image_id)
    {
        try {
            $this->_db->delete('DELETE FROM ansel_image_attributes WHERE image_id = ' . (int)$image_id);
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Get image's attribtues from storage
     *
     * @param int $image_id  The image id
     *
     * @return array  A image attribute hash
     * @throws Horde_Exception
     */
    public function getImageAttributes($image_id)
    {
        try {
            return $this->_db->selectAssoc(
                'SELECT attr_name, attr_value FROM ansel_image_attributes WHERE '
                . ' image_id = ' . (int)$image_id);
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }

        return $results;
    }

    /**
     * Set image sort order
     *
     * @param integer $imageId  The image id
     * @param integer $pos      The new sort order position
     *
     * @throws Ansel_Exception
     */
    public function setImageSortOrder($imageId, $pos)
    {
         try {
           $this->_db->update(
               'UPDATE ansel_images SET image_sort = '
               . (int)$pos . ' WHERE image_id = ' . (int)$imageId);
        } catch (Horde_Db_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Horde_Exception($e);
        }
    }

    /**
     * Return the images corresponding to the given ids.
     *
     * @param array $params function parameters:
     *  <pre>
     *    'ids'        - An array of image ids to fetch.
     *    'preserve'   - Preserve the order of the image ids when returned.
     *    'gallery_id' - Return all images from requested gallery (ignores 'ids').
     *    'from'       - If passing a gallery, start at this image.
     *    'count'      - If passing a gallery, return this many images.
     *  </pre>
     *
     * @return array An array of Ansel_Image objects.
     * @throws Ansel_Exception, Horde_Exception_NotFound, InvalidArgumentException
     */
    public function getImages(array $params = array())
    {
        // First check if we want a specific gallery or a list of images
        if (!empty($params['gallery_id'])) {
            $sql = 'SELECT ' . $this->_getImageFields()
                . ' FROM ansel_images WHERE gallery_id = '
                . $params['gallery_id'] . ' ORDER BY image_sort';
        } elseif (!empty($params['ids']) && is_array($params['ids']) && count($params['ids']) > 0) {
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images WHERE image_id IN (';
            $i = 1;
            $cnt = count($params['ids']);
            foreach ($params['ids'] as $id) {
                $sql .= (int)$id . (($i++ < $cnt) ? ',' : ');');
            }
        } else {
            throw new InvalidArgumentException('Ansel_Storage::getImages requires either a gallery_id or an array of image ids');
        }

        // Limit the query?
        if (isset($params['count']) && isset($params['from'])) {
            $sql = $this->_db->addLimitOffset($sql, array('limit' => $params['count'], 'offset' => $params['from']));
        }
        try {
            $images = $this->_db->select($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($images);
        }
        // Throw exception if we asked for specific image ids and not found.
        if (empty($images) && empty($params['gallery_id'])) {
            throw new Horde_Exception_NotFound(_("Images not found"));
        } elseif (empty($images)) {
            return array();
        }

        $return = array();
        foreach ($images as $image) {
            $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $return[$image['image_id']] = new Ansel_Image($image);
            $this->_images[(int)$image['image_id']] = &$return[$image['image_id']];
        }

        // Need to get comment counts if comments are enabled
        $ccounts = $this->_getImageCommentCounts(array_keys($return));
        if (count($ccounts)) {
            foreach ($return as $key => $image) {
                $return[$key]->commentCount = (!empty($ccounts[$key]) ? $ccounts[$key] : 0);
            }
        }

        // Preserve the order the images_ids were passed in
        if (empty($params['gallery_id']) && !empty($params['preserve'])) {
            foreach ($params['ids'] as $id) {
                $ordered[$id] = $return[$id];
            }
            return $ordered;
        }

        return $return;
    }

    /**
     * Get the total number of comments for an image.
     *
     * @param array $ids  Array of image ids
     *
     * @return array of results. @see forums/numMessagesBatch api call
     */
    protected function _getImageCommentCounts(array $ids)
    {
        global $conf, $registry;

        // Need to get comment counts if comments are enabled
        if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && $GLOBALS['registry']->getAuth())) &&
            $registry->hasMethod('forums/numMessagesBatch')) {

            return $registry->call('forums/numMessagesBatch', array($ids, 'ansel'));
        }

        return array();
    }

    /**
     * Returns a list of Ansel_Images of the most recently added images for the
     * current user.
     *
     * @param array $galleries  An array of gallery ids to search in. If
     *                          left empty, will search all galleries
     *                          with Horde_Perms::SHOW.
     * @param integer $limit    The maximum number of images to return
     * @param string $slugs     An array of gallery slugs.
     * @param string $where     Additional where clause
     *
     * @return array An array of Ansel_Image objects
     * @throws Ansel_Exception
     */
    public function getRecentImages(array $galleries = array(), $limit = 10, array $slugs = array())
    {
        $results = array();

        if (!count($galleries) && !count($slugs)) {
            // Don't need the Ansel_Gallery object, so save some resources and
            // only query the share system.
            foreach ($this->_shares->listShares($GLOBALS['registry']->getAuth()) as $share) {
                $galleries[] = $share->getId();
            }
            if (empty($galleries)) {
                return array();
            }
        }
        if (!count($slugs)) {
            // Searching by gallery_id
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images '
                   . 'WHERE gallery_id IN ('
                   . str_repeat('?, ', count($galleries) - 1) . '?) ';
            $criteria = $galleries;
        } elseif (count($slugs)) {
            // Searching by gallery_slug so we need to join the share table
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images LEFT JOIN '
                . $this->_shares->getTable() . ' ON ansel_images.gallery_id = '
                . $this->_shares->getTable() . '.share_id ' . 'WHERE attribute_slug IN ('
                . str_repeat('?, ', count($slugs) - 1) . '?) ';
            $criteria = $slugs;
        }

        $sql .= ' ORDER BY image_uploaded_date DESC';
        if ($limit > 0) {
            $sql = $this->_db->addLimitOffset($sql, array('limit' => (int)$limit));
        }
        try {
            $images = $this->_db->selectAll($sql, $criteria);
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }

        foreach($images as $image) {
            $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $results[] = new Ansel_Image($image);
        }

        return $results;
    }

    /**
     * Check if a gallery exists. Need to do this here so we can also check by
     * gallery slug.
     *
     * @param integer $gallery_id  The gallery id
     * @param string  $slug        The gallery slug
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function galleryExists($gallery_id = null, $slug = null)
    {
        if (empty($slug)) {
            $results = $this->_shares->idExists($gallery_id);
        } else {
            $results = $this->_shares->countShares($GLOBALS['registry']->getAuth(), Horde_Perms::READ, array('slug' => $slug));
        }

        return (bool)$results;
    }

   /**
    * Return the count of galleries that the user has specified permissions to
    * and that match any of the requested attributes.
    *
    * @param string userid  The user to check access for.
    * @param array $params  Parameter array:
    *<pre>
    *  (integer)perm          The level of permissions to require for a
    *                         gallery to return it [Horde_Perms::SHOW]
    *  (mixed)attributes      Restrict the galleries counted to those
    *                         matching $attributes. An array of
    *                         attribute/values pairs or a gallery owner
    *                         username.
    * (Ansel_Gallery)parent   The parent share to start counting at.
    * (boolean)all_levels      Return all levels, or just the direct children of
    *                         $parent? [true]
    * (array)tags             Filter results by galleries tagged with tags.
    *</pre>
    *
    * @return integer  The count
    * @throws Ansel_Exception
    */
    public function countGalleries($userid, array $params = array())
    {
        static $counts;

        $oparams = new Horde_Support_Array($params);
        if ($oparams->parent) {
            $parent_id = $oparams->parent->id;
        } else {
            $parent_id = null;
        }
        $perm = $oparams->get('perm', Horde_Perms::SHOW);
        $key = "$userid,$perm,$parent_id,{$oparams->all_levels}" . serialize($oparams->get('attributes', array())) . serialize($oparams->get('tags', array()));
        if (isset($counts[$key])) {
            return $counts[$key];
        }

        // Unfortunately, we need to go the long way around to count shares if
        // we are filtering by tags.
        if ($oparams->tags) {
            $count = count($this->listGalleries($params));
        } else {
            try {
                $count = $this->_shares->countShares(
                    $userid,
                    $perm, $oparams->get('attributes', array()),
                    $parent_id,
                    $oparams->get('all_levels', true));
            } catch (Horde_Share_Exception $e) {
                throw new Ansel_Exception($e);
            }
        }
        $counts[$key] = $count;

        return $count;
    }

   /**
    * Retrieves the current user's gallery list from storage.
    *
    * @param array $params  Optional parameters:
    *   <pre>
    *     (integer)perm      The permissions filter to use [Horde_Perms::SHOW]
    *     (mixed)attributes  Restrict the galleries returned to those matching
    *                        the filters. Can be an array of attribute/values
    *                        pairs or a gallery owner username.
    *     (integer)parent    The parent share to start listing at.
    *     (boolean)all_levels If set, return all levels below parent, not just
    *                        direct children [TRUE]
    *     (integer)from      The gallery to start listing at.
    *     (integer)count     The number of galleries to return.
    *     (string)sort_by    Attribute to sort by.
    *     (integer)direction The direction to sort by [Ansel::SORT_ASCENDING]
    *     (array)tags        An array of tags to limit results by.
    *   </pre>
    *
    * @return array An array of Ansel_Gallery objects
    * @throws Ansel_Exception
    */
    public function listGalleries($params = array())
    {
        $galleries = array();
        try {
            if (!empty($params['tags'])) {
                $count = !empty($params['count']) ? $params['count'] : null;
                $from = !empty($params['from']) ? $params['from'] : null;
                unset($params['count'], $params['from']);
                $shares = $this->_shares->listShares($GLOBALS['registry']->getAuth(), $params);
                if (!empty($params['attributes']) && !is_array($params['attributes'])) {
                    $user = $params['attributes'];
                } elseif (!empty($params['attributes']['owner'])) {
                    $user = $params['attributes']['owner'];
                } else {
                    $user = null;
                }
                $tagged = $GLOBALS['injector']
                    ->getInstance('Ansel_Tagger')
                    ->search(
                        $params['tags'],
                        array(
                            'type' => 'gallery',
                            'user' => $user));

                foreach ($shares as $share) {
                    if (in_array($share->getId(), $tagged['galleries'])) {
                        $galleries[] = $share;
                    }
                }
                $galleries = array_slice($galleries, $from, $count);
            } else {
                $galleries = $this->_shares->listShares($GLOBALS['registry']->getAuth(), $params);
            }
            $shares = $this->buildGalleries($galleries);
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }

        return $shares;
    }

    /**
     * Returns a list of ALL galleries, regardless of permissions.
     *
     * @return array
     */
    public function listAllGalleries()
    {
        return $this->buildGalleries($this->_shares->listAllShares());
    }

    /**
     * Retrieve json data for an arbitrary list of image ids, not necessarily
     * from the same gallery.
     *
     * @param array $images        An array of image ids
     * @param Ansel_Style $style   A gallery style to force if requesting
     *                             pretty thumbs.
     * @param boolean $full        Generate full urls
     * @param string $image_view   Which image view to use? screen, thumb etc..
     * @param boolean $view_links  Include links to the image view
     *
     * @return string  The json data
     */
    public function getImageJson(array $images, Ansel_Style $style = null,
        $full = false, $image_view = 'mini', $view_links = false)
    {
        $galleries = array();
        if (is_null($style)) {
            $style = Ansel::getStyleDefinition('ansel_default');
        }

        $json = array();

        foreach ($images as $id) {
            $image = $this->getImage($id);
            $gallery_id = abs($image->gallery);
            if (empty($galleries[$gallery_id])) {
                $galleries[$gallery_id]['gallery'] = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($gallery_id);
            }

            // Any authentication that needs to take place for any of the
            // images included here MUST have already taken place or the
            // image will not be incldued in the output.
            if (!isset($galleries[$gallery_id]['perm'])) {
                $galleries[$gallery_id]['perm'] =
                    ($galleries[$gallery_id]['gallery']->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ) &&
                     $galleries[$gallery_id]['gallery']->isOldEnough() &&
                     !$galleries[$gallery_id]['gallery']->hasPasswd());
            }

            if ($galleries[$gallery_id]['perm']) {
                $data = array((string)Ansel::getImageUrl($image->id, $image_view, $full, $style),
                    htmlspecialchars($image->filename),
                    $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($image->caption, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL)),
                    $image->id,
                    0);

                if ($view_links) {
                    $data[] = (string)Ansel::getUrlFor('view',
                        array('gallery' => $image->gallery,
                              'image' => $image->id,
                              'view' => 'Image',
                              'slug' => $galleries[$gallery_id]['gallery']->get('slug')),
                        $full);

                    $data[] = (string)Ansel::getUrlFor('view',
                        array('gallery' => $image->gallery,
                              'slug' => $galleries[$gallery_id]['gallery']->get('slug'),
                              'view' => 'Gallery'),
                        $full);
                }

                $json[] = $data;
            }

        }

        return Horde_Serialize::serialize($json, Horde_Serialize::JSON);
    }

    /**
     * Returns a random Ansel_Gallery from a list fitting the search criteria.
     *
     * @see Ansel_Storage::listGalleries()
     */
    public function getRandomGallery(array $params = array())
    {
        $galleries = $this->listGalleries($params);
        if (!$galleries) {
            return false;
        }

        return $galleries[array_rand($galleries)];
    }

    /**
     * Lists a slice of the image ids in the given gallery.
     *
     * @param array $params  Filter parameters.
     *<pre>
     *  integer|array 'gallery_id'  - A gallery id to list images from
     *  integer 'offset'            - The image to start listing from
     *  integer 'limit'             - How many images to return
     *  array|string 'fields'       - The fields to return
     *  string 'sort'               - The field to sort by.
     *  array  'filter'             - Additional filters. Each element is an
     *                                array containing 'property', 'op', and
     *                                'value' keys. Passing 'IN' as the 'op'
     *                                and an array as 'value' will produce a
     *                                SQL IN conditional.
     *</pre>
     *
     * @return array  An array of images. Either an array of ids, or an array
     *                of field values, keyed by id.
     * @throws Ansel_Exception, InvalidArgumentException
     */
    public function listImages(array $params = array())
    {
        $params = new Horde_Support_Array($params);
        if (is_array($params['fields'])) {
            $field_count = count($params['fields']);
            $params['fields'] = implode(', ', $params['fields']);
        } elseif ($params['fields'] == '*') {
            // The count is not important, as long as it's > 1
            $field_count = 2;
        } else {
            $field_count = substr_count($params->get('fields', 'image_id'), ',') + 1;
        }

        if (is_array($params['sort'])) {
            $params['sort'] = implode(', ', $params['sort']);
        }

        if (is_array($params['gallery_id'])) {
            $query_where = 'WHERE gallery_id IN (' . implode(',', $params['gallery_id']) . ')';
        } elseif ($params['gallery_id']) {
            $query_where = 'WHERE gallery_id = ' . $params['gallery_id'];
        } else {
            $query_where = '';
        }
        if ($params['filter']) {
            foreach ($params['filter'] as $filter) {
                $query_where .= (!empty($query_where) ? ' AND ' : ' WHERE ')
                    . $this->_toImageDriverName($filter['property'])
                    . ' ' . $filter['op'] . ' ' .
                    (is_array($filter['value']) ? '(' . implode(',', $filter['value']) . ')' : $filter['value']);
            }
        }
        $sql = 'SELECT ' . $params->get('fields', 'image_id')
            . ' FROM ansel_images ' . $query_where
            . ' ORDER BY ' . $params->get('sort', 'image_sort');
        $sql = $this->_db->addLimitOffset(
            $sql,
            array(
                'limit' => $params->get('limit', 0),
                'offset' => $params->get('offset', 0))
        );
        try {
            if ($field_count > 1) {
                $results = $this->_db->selectAll($sql);
                $images = array();
                foreach ($results as $image) {
                    $images[$image['image_id']] = $image;
                }
                return $images;
            } else {
                return $this->_db->selectValues($sql);
            }
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Return images' geolocation data.
     *
     * @param array $image_ids  An array of image_ids to look up.
     * @param integer $gallery  A gallery id. If this is provided, will return
     *                          all images in the gallery that have geolocation
     *                          data ($image_ids would be ignored).
     *
     * @return array of geodata
     */
    public function getImagesGeodata(array $image_ids = array(), $gallery = null)
    {
        if ((!is_array($image_ids) || count($image_ids) == 0) && empty($gallery)) {
            return array();
        }
        $params = array(
            'fields' => array(
                'image_id as id',
                'image_id',
                'image_latitude',
                'image_longitude',
                'image_location'),
            'filter' => array(
                array(
                    'property' => 'latitude',
                    'op' => '!=',
                    'value' => "''"))
        );
        if (!empty($gallery)) {
            $params['gallery_id'] = (int)$gallery;
        } elseif (count($image_ids) > 0) {
            $params['filter'][] = array(
                'property' => 'id',
                'op' => 'IN',
                'value' => $image_ids);
        } else {
            return array();
        }

        return $this->listImages($params);
    }

    /**
     * Like getRecentImages, but returns geotag data for the most recently added
     * images from the current user. Useful for providing images to help locate
     * images at the same place.
     *
     * @param string $user    Limit images to this user
     * @param integer $start  Start a slice at this image number
     * @param integer $count  Include this many images
     *
     * @return array An array of image ids
     *
     */
    public function getRecentImagesGeodata($user = null, $start = 0, $count = 8)
    {
        $galleries = $this->listGalleries(
            array(
                'perm' => Horde_Perms::EDIT,
                'attributes' => $user
            )
        );
        $ids = array();
        foreach ($galleries as $gallery) {
            $ids[] = $gallery->id;
        }
        if (empty($ids)) {
            return array();
        }

        $params = array(
            'offset' => $start,
            'limit' => $count,
            'fields' => array(
                'image_id as id',
                'image_id',
                'gallery_id',
                'image_latitude',
                'image_longitude',
                'image_location'),
            'gallery_id' => $ids,
            'filter' => array(
                array(
                    'property' => 'latitude',
                    'op' => '!=',
                    'value' => "''")
                ),
            'sort' => 'image_geotag_date DESC');

        return $this->listImages($params);
    }

    /**
     * Search for a textual location string from the passed in search token.
     * Used for location autocompletion.
     *
     * @param string $search  Search fragment for autocompleting location strings
     *
     * @return array  The results
     * @throws Ansel_Exception
     */
    public function searchLocations($search = '')
    {
        $sql = 'SELECT DISTINCT image_location, image_latitude, image_longitude FROM ansel_images WHERE LENGTH(image_location) > 0';
        if (strlen($search)) {
            $sql .= ' AND image_location LIKE ' . $this->_db->quoteString("$search%");
        }
        try {
            return $this->_db->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Set the gallery id for a set of images. Useful for bulk updating images
     * when moving from one gallery to another.
     *
     * @param array $image_ids     An array of image ids
     * @param integer $gallery_id  The gallery id to move the images to.
     *
     * @throws Ansel_Exception
     */
    public function setImagesGallery(array $image_ids, $gallery_id)
    {
        try {
            $this->_db->update('UPDATE ansel_images SET gallery_id = ' . $gallery_id . ' WHERE image_id IN (' . implode(',', $image_ids) . ')');
        } catch (Horde_Db_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Deletes an Ansel_Image from data storage.
     *
     * @param integer $image_id  The image id(s) to remove.
     *
     * @throws Ansel_Exception
     */
    public function removeImage($image_id)
    {   try {
            $this->_db->delete('DELETE FROM ansel_images WHERE image_id = ' . (int)$image_id);
            $this->_db->delete('DELETE FROM ansel_image_attributes WHERE image_id = ' . (int)$image_id);
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Helper function to get a string of field names
     *
     * @return string
     */
    protected function _getImageFields($alias = '')
    {
        $fields = array(
            'image_id', 'gallery_id', 'image_filename', 'image_type',
            'image_caption', 'image_uploaded_date', 'image_sort',
            'image_faces', 'image_original_date', 'image_latitude',
            'image_longitude', 'image_location', 'image_geotag_date');
        if (!empty($alias)) {
            foreach ($fields as $field) {
                $new[] = $alias . '.' . $field;
            }
            return implode(', ', $new);
        }

        return implode(', ', $fields);
    }

    /**
     * Ensure the style hash is recorded in the database.
     *
     * @param string $hash  The hash to record.
     */
    public function ensureHash($hash)
    {
        $query = 'SELECT COUNT(*) FROM ansel_hashes WHERE style_hash = ?';
        try {
            $results = $this->_db->selectValue($query, array($hash));
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }
        if (!$results) {
            try {
                $this->_db->insert('INSERT INTO ansel_hashes (style_hash) VALUES(?)', array($hash));
            } catch (Horde_Db_Exception $e) {
                throw new Ansel_Exception($e);
            }
        }
    }

    /**
     * Get a list of all known styleHashes.
     *
     * @return array  An array of style hashes.
     */
    public function getHashes()
    {
        try {
            return $this->_db->selectValues('SELECT style_hash FROM ansel_hashes');
        } catch (Horde_Db_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Build a single Ansel_Gallery object from a Horde_Share_Object
     *
     * @param Horde_Share_Object $share  The share
     *
     * @return Ansel_Gallery
     */
    public function buildGallery(Horde_Share_Object $share)
    {
        return current($this->buildGalleries(array($share)));
    }

    /**
     * Build an array of Ansel_Gallery objects from an array of
     * Horde_Share_Object objects.
     *
     * @param array $shares  An array of Horde_Share_Object objects.
     *
     * @return array Ansel_Gallery objects.
     */
    public function buildGalleries(array $shares)
    {
        $results = array();
        foreach ($shares as $share) {
            $results[] = new Ansel_Gallery($share);
        }

        return $results;
    }

    /**
     * Convert an Ansel_Image property to it's backend storage field name.
     *
     * @param string $field  The field name
     *
     * @return string  The converted field name suitable for use in backend.
     */
    protected function _toImageDriverName($field)
    {
        switch ($field) {
            case 'id':
            case 'filename':
            case 'type':
            case 'caption':
            case 'sort':
            case 'faces':
            case 'latitude':
            case 'longitude':
            case 'location':
                return 'image_' . $field;
            case 'uploadedDate':
                return 'image_uploaded_date';
            case 'originalDate':
                return 'image_original_date';
            case 'geotagDate':
                return 'image_geotag_date';
        }
    }

}
