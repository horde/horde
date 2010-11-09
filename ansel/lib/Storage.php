<?php
/**
 * Class for interfacing with back end data storage.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Storage
{
    /**
     * database handle
     *
     * @var MDB2
     */
    private $_db = null;

    /**
     * The Horde_Shares object to use for this scope.
     *
     * @var Horde_Share
     */
    private $_shares = null;

    /**
     * Local cache of retrieved images
     *
     * @var array
     */
    private $_images = array();

    /**
     * Const'r
     *
     * @param Horde_Share_Sql_Hierarchical  The share object
     *
     * @return Ansel_Storage
     */
    public function __construct(Horde_Core_Share_Driver $shareOb)
    {
        /* This is the only supported share backend for Ansel */
        $this->_shares = $shareOb;

        /* Ansel_Gallery is just a subclass of Horde_Share_Object */
        $this->_shares->setShareClass('Ansel_Gallery');

        /* Database handle */
        $this->_db = $GLOBALS['ansel_db'];
    }

    /**
     * Property accessor
     *
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
    * Create and initialise a new gallery object.
    *
    * @param array $attributes         The gallery attributes.
    * @param object Horde_Perms $perm  The permissions for the gallery if the
    *                                  defaults are not desirable.
    * @param mixed  $parent            The id of the parent gallery (if any).
    *
    * @return Ansel_Gallery  A new gallery object.
    * @throws Ansel_Exception
    */
    public function createGallery($attributes = array(), $perm = null, $parent = null)
    {
        /* Required values. */
        if (empty($attributes['owner'])) {
            $attributes['owner'] = $GLOBALS['registry']->getAuth();
        }
        if (empty($attributes['name'])) {
            $attributes['name'] = _("Unnamed");
        }
        if (empty($attributes['desc'])) {
            $attributes['desc'] = '';
        }

        /* Default values */
        $attributes['default_type'] = isset($attributes['default_type']) ? $attributes['default_type'] : 'auto';
        $attributes['default'] = isset($attributes['default']) ? (int)$attributes['default'] : 0;
        $attributes['default_prettythumb'] = isset($attributes['default_prettythumb']) ? $attributes['default_prettythumb'] : '';
        // No value for style now means to use the 'default_ansel' style as defined in styles.php
        $attributes['style'] = isset($attributes['style']) ? $attributes['style'] : '';
        $attributes['date_created'] = time();
        $attributes['last_modified'] = $attributes['date_created'];
        $attributes['images'] = isset($attributes['images']) ? (int)$attributes['images'] : 0;
        $attributes['slug'] = isset($attributes['slug']) ? $attributes['slug'] : '';
        $attributes['age'] = isset($attributes['age']) ? (int)$attributes['age'] : 0;
        $attributes['download'] = isset($attributes['download']) ? $attributes['download'] : $GLOBALS['prefs']->getValue('default_download');
        $attributes['view_mode'] = isset($attributes['view_mode']) ? $attributes['view_mode'] : 'Normal';
        $attributes['passwd'] = isset($attributes['passwd']) ? $attributes['passwd'] : '';

        /* Don't pass tags to the share creation method */
        if (isset($attributes['tags'])) {
            $tags = $attributes['tags'];
            unset($attributes['tags']);
        } else {
            $tags = array();
        }

        /* Check for slug uniqueness */
        if (!empty($attributes['slug']) &&
            $this->slugExists($attributes['slug'])) {
            throw new Horde_Exception(sprintf(_("The slug \"%s\" already exists."), $attributes['slug']));
        }

        /* Create the gallery */
        try {
            $gallery = $this->_shares->newShare($GLOBALS['registry']->getAuth(), '');
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage, 'ERR');
            throw new Ansel_Exception($e);
        }
        Horde::logMessage('New Ansel_Gallery object instantiated', 'DEBUG');

        /* Set the gallery's parent if needed */
        if (!is_null($parent)) {
            $result = $gallery->setParent($parent);

            /* Clear the parent from the cache */
            if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $parent);
            }
        }

        /* Fill up the new gallery */
        foreach ($attributes as $key => $value) {
            $gallery->set($key, $value);
        }

        /* Save it to storage */
        try {
            $result = $this->_shares->addShare($gallery);
        } catch (Horde_Share_Exception $e) {
            $error = sprintf(_("The gallery \"%s\" could not be created: %s"),
                             $attributes['name'], $e->getMessage());
            Horde::logMessage($error, 'ERR');
            throw new Ansel_Exception($error);
        }

        /* Convenience */
        $gallery->id = $gallery->getId();

        /* Add default permissions. */
        if (empty($perm)) {
            $perm = $gallery->getPermission();

            /* Default permissions for logged in users */
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

            /* Default guest permissions */
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

            /* Default user groups permissions */
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
                $groups = $GLOBALS['injector']->getInstance('Horde_Group');
                $group_list = $groups->getGroupMemberships($GLOBALS['registry']->getAuth());
                if (count($group_list)) {
                    foreach ($group_list as $group_id => $group_name) {
                        $perm->addGroupPermission($group_id, $perms, false);
                    }
                }
            }
        }
        $gallery->setPermission($perm, true);

        /* Initial tags */
        if (count($tags)) {
            $gallery->setTags($tags);
        }

        return $gallery;
    }

    /**
     * Check that a slug exists.
     *
     * @param string $slug  The slug name
     *
     * @return integer  The share_id the slug represents, or 0 if not found.
     */
    public function slugExists($slug)
    {
        // An empty slug should never match.
        if (!strlen($slug)) {
            return 0;
        }

        $stmt = $this->_db->prepare('SELECT share_id FROM '
            . $this->_shares->getTable() . ' WHERE attribute_slug = ?');

        if ($stmt instanceof PEAR_Error) {
            Horde::logMessage($stmt, 'ERR');
            return 0;
        }

        $result = $stmt->execute($slug);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            return 0;
        }
        if (!$result->numRows()) {
            return 0;
        }

        $slug = $result->fetchRow();

        $result->free();
        $stmt->free();

        return $slug[0];
    }

    /**
     * Retrieve an Ansel_Gallery given the gallery's slug
     *
     * @param string $slug  The gallery slug
     * @param array $overrides  An array of attributes that should be overridden
     *                          when the gallery is returned.
     *
     * @return Ansel_Gallery object
     * @throws Horde_Exception_NotFound
     */
    public function getGalleryBySlug($slug, $overrides = array())
    {
        $id = $this->slugExists($slug);
        if ($id) {
            return $this->getGallery($id, $overrides);
        } else {
            throw new Horde_Exception_NotFound(sprintf(_("Gallery %s not found."), $slug));
        }
     }

    /**
     * Retrieve an Ansel_Gallery given the share id
     *
     * @param integer $gallery_id  The share_id to fetch
     * @param array $overrides     An array of attributes that should be
     *                             overridden when the gallery is returned.
     *
     * @return Ansel_Gallery
     * @throws Ansel_Exception
     */
    public function getGallery($gallery_id, $overrides = array())
    {
       if (!count($overrides) && $GLOBALS['conf']['ansel_cache']['usecache'] &&
           ($gallery = $GLOBALS['injector']->getInstance('Horde_Cache')->get('Ansel_Gallery' . $gallery_id, $GLOBALS['conf']['cache']['default_lifetime'])) !== false) {

           $cached_gallery = @unserialize($gallery);
           if ($cached_gallery) { return $cached_gallery; }
       }

       try {
           $result = $this->_shares->getShareById($gallery_id);
       } catch (Horde_Share_Exception $e) {
           throw new Ansel_Exception($e);
       }

       // Don't cache if we have overridden anything
       if (!count($overrides)) {
           if ($GLOBALS['conf']['ansel_cache']['usecache']) {
               $GLOBALS['injector']->getInstance('Horde_Cache')->set('Ansel_Gallery' . $gallery_id, serialize($result));
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
     * @param array $slugs  The gallery slugs
     *
     * @return array of Ansel_Gallery objects
     * @throws Ansel_Exception
     */
    public function getGalleriesBySlugs($slugs)
    {
        $sql = 'SELECT share_id FROM ' . $this->_shares->getTable()
            . ' WHERE attribute_slug IN (' . str_repeat('?, ', count($slugs) - 1) . '?)';

        $stmt = $this->_shares->getReadDb()->prepare($sql);
        if ($stmt instanceof PEAR_Error) {
            throw new Horde_Exception($stmt->getMessage());
        }
        $result = $stmt->execute($slugs);
        if ($result instanceof PEAR_Error) {
            throw new Ansel_Exception($result);
        }
        $ids = array_values($result->fetchCol());
        $shares = $this->_shares->getShares($ids);

        $stmt->free();
        $result->free();

        return $shares;
    }

    /**
     * Retrieve an array of Ansel_Gallery objects for the requested ids
     *
     * @return array of Ansel_Gallery objects
     */
    public function getGalleries($ids)
    {
        return $this->_shares->getShares($ids);
    }

    /**
     * Empties a gallery of all images.
     *
     * @param Ansel_Gallery $gallery  The ansel gallery to empty.
     *
     * @return void
     */
    public function emptyGallery(Ansel_Gallery $gallery)
    {
        $gallery->clearStacks();
        $images = $gallery->listImages();
        foreach ($images as $image) {
            // Pretend we are a stack so we don't update the images count
            // for every image deletion, since we know the end result will
            // be zero.
            $gallery->removeImage($image, true);
        }
        $gallery->set('images', 0, true);

        // Clear the OtherGalleries widget cache
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_OtherGalleries' . $gallery->get('owner'));
        }
    }

    /**
     * Removes an Ansel_Gallery.
     *
     * @param Ansel_Gallery $gallery  The gallery to delete
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function removeGallery(Ansel_Gallery $gallery)
    {
        /* Get any children and empty them */
        $children = $gallery->getChildren(null, null, true);
        foreach ($children as $child) {
            $this->emptyGallery($child);
            $child->setTags(array());
        }

        /* Now empty the selected gallery of images */
        $this->emptyGallery($gallery);

        /* Clear all the tags. */
        $gallery->setTags(array());

        /* Get the parent, if it exists, before we delete the gallery. */
        $parent = $gallery->getParent();
        $id = $gallery->id;

        /* Delete the gallery from storage */
        try {
            $this->_shares->removeShare($gallery);
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }

        /* Expire the cache */
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $id);
        }

        /* See if we need to clear the has_subgalleries field */
        if ($parent instanceof Ansel_Gallery) {
            if (!$parent->countChildren($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW, false)) {
                $parent->set('has_subgalleries', 0, true);
                if ($GLOBALS['conf']['ansel_cache']['usecache']) {
                    $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $parent->id);
                }
            }
        }

        return true;
    }

    /**
     * Returns the image corresponding to the given id.
     *
     * @param integer $id  The ID of the image to retrieve.
     *
     * @return Ansel_Image  The image object corresponding to the given name.
     * @throws Ansel_Exception, Horde_Exception_NotFound
     */
    public function &getImage($id)
    {
        if (isset($this->_images[$id])) {
            return $this->_images[$id];
        }

        $q = $this->_db->prepare('SELECT ' . $this->_getImageFields() . ' FROM ansel_images WHERE image_id = ?');
        if ($q instanceof PEAR_Error) {
            Horde::logMessage($q, 'ERR');
            throw new Ansel_Exception($q);
        }
        $result = $q->execute((int)$id);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Ansel_Exception($result);
        }
        $image = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $q->free();
        $result->free();
        if (is_null($image)) {
            throw new Horde_Exception_NotFound(_("Photo not found"));
        } elseif ($image instanceof PEAR_Error) {
            Horde::logMessage($image, 'ERR');
            throw new Ansel_Exception($image);
        } else {
            $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $this->_images[$id] = new Ansel_Image($image);

            return $this->_images[$id];
        }
    }

    /**
     * Save image details to storage. Does NOT update the cached image files.
     *
     * @param Ansel_Image $image
     *
     * @return integer The image id
     *
     * @throws Ansel_Exception
     */
    public function saveImage(Ansel_Image $image)
    {
        /* If we have an id, then it's an existing image.*/
        if ($image->id) {
            $update = $this->_db->prepare('UPDATE ansel_images SET image_filename = ?, image_type = ?, image_caption = ?, image_sort = ?, image_original_date = ?, image_latitude = ?, image_longitude = ?, image_location = ?, image_geotag_date = ? WHERE image_id = ?');
            if ($update instanceof PEAR_Error) {
                Horde::logMessage($update, 'ERR');
                throw new Ansel_Exception($update);
            }
            $result = $update->execute(array(Horde_String::convertCharset($image->filename, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                                             $image->type,
                                             Horde_String::convertCharset($image->caption, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                                             $image->sort,
                                             $image->originalDate,
                                             $image->lat,
                                             $image->lng,
                                             $image->location,
                                             $image->geotag_timestamp,
                                             $image->id));
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($update, 'ERR');
                throw new Ansel_Exception($result);
            }
            $update->free();

            return $result;
        }

        /* Saving a new Image */
        if (!$image->gallery || !strlen($image->filename) || !$image->type) {
            throw new Ansel_Exception(_("Incomplete photo"));
        }

        /* Get the next image_id */
        $image_id = $this->_db->nextId('ansel_images');
        if ($image_id instanceof PEAR_Error) {
            throw new Ansel_Exception($image_id);
        }

        /* Prepare the SQL statement */
        $insert = $this->_db->prepare('INSERT INTO ansel_images (image_id, gallery_id, image_filename, image_type, image_caption, image_uploaded_date, image_sort, image_original_date, image_latitude, image_longitude, image_location, image_geotag_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if ($insert instanceof PEAR_Error) {
            Horde::logMessage($insert, 'ERR');
            throw new Ansel_Exception($insert);
        }

        /* Perform the INSERT */
        $result = $insert->execute(array($image_id,
                                         $image->gallery,
                                         Horde_String::convertCharset($image->filename, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                                         $image->type,
                                         Horde_String::convertCharset($image->caption, 'UTF-8', $GLOBALS['conf']['sql']['charset']),
                                         $image->uploaded,
                                         $image->sort,
                                         $image->originalDate,
                                         $image->lat,
                                         $image->lng,
                                         $image->location,
                                         (empty($image->lat) ? 0 : $image->uploaded)));
        $insert->free();
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Ansel_Exception($result);
        }

        /* Keep the image_id */
        $image->id = $image_id;

        return $image->id;
    }

    /**
     * Store an image attributes to storage
     *
     * @param integer $image_id    The image id
     * @param string  $attributes  The attribute name
     * @param string  $value       The attrbute value
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function saveImageAttribute($image_id, $attribute, $value)
    {
        $insert = $this->_db->prepare('INSERT INTO ansel_image_attributes (image_id, attr_name, attr_value) VALUES (?, ?, ?)');
        $result = $insert->execute(array($image_id, $attribute, Horde_String::convertCharset($value, 'UTF-8', $GLOBALS['conf']['sql']['charset'])));
        if ($result instanceof PEAR_Error) {
            throw new Ansel_Exception($result);
        }
        $insert->free();
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
     * @return array of Ansel_Image objects.
     * @throws Ansel_Exception, Horde_Exception_NotFound
     */
    public function getImages($params = array())
    {
        /* First check if we want a specific gallery or a list of images */
        if (!empty($params['gallery_id'])) {
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images WHERE gallery_id = ' . $params['gallery_id'] . ' ORDER BY image_sort';
        } elseif (!empty($params['ids']) && is_array($params['ids']) && count($params['ids']) > 0) {
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images WHERE image_id IN (';
            $i = 1;
            $cnt = count($params['ids']);
            foreach ($params['ids'] as $id) {
                $sql .= (int)$id . (($i++ < $cnt) ? ',' : ');');
            }
        } else {
            throw new Ansel_Exception('Ansel_Storage::getImages requires either a gallery_id or an array of image ids');
        }

        /* Limit the query? */
        if (isset($params['count']) && isset($params['from'])) {
            $this->_db->setLimit($params['count'], $params['from']);
        }

        $images = $this->_db->query($sql);
        if ($images instanceof PEAR_Error) {
            throw new Ansel_Exception($images);
        } elseif ($images->numRows() == 0 && empty($params['gallery_id'])) {
            $images->free();
            throw new Horde_Exception_NotFound(_("Images not found"));
        } elseif ($images->numRows() == 0) {
            return array();
        }

        $return = array();
        while ($image = $images->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $return[$image['image_id']] = new Ansel_Image($image);
            $this->_images[(int)$image['image_id']] = &$return[$image['image_id']];
        }
        $images->free();

        /* Need to get comment counts if comments are enabled */
        $ccounts = $this->_getImageCommentCounts(array_keys($return));
        if (!($ccounts instanceof PEAR_Error) && count($ccounts)) {
            foreach ($return as $key => $image) {
                $return[$key]->commentCount = (!empty($ccounts[$key]) ? $ccounts[$key] : 0);
            }
        }

        /* Preserve the order the images_ids were passed in */
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
    protected function _getImageCommentCounts($ids)
    {
        global $conf, $registry;

        /* Need to get comment counts if comments are enabled */
        if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && $GLOBALS['registry']->getAuth())) &&
            $registry->hasMethod('forums/numMessagesBatch')) {

            return $registry->call('forums/numMessagesBatch', array($ids, 'ansel'));
        }

        return array();
    }

    /**
     * Return a list of image ids of the most recently added images.
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
    public function getRecentImages($galleries = array(), $limit = 10, $slugs = array())
    {
        $results = array();

        if (!count($galleries) && !count($slugs)) {
            $sql = 'SELECT DISTINCT ' . $this->_getImageFields('i') . ' FROM ansel_images i, '
            . str_replace('WHERE' , ' WHERE i.gallery_id = s.share_id AND (', substr($this->_shares->getShareCriteria($GLOBALS['registry']->getAuth()), 5)) . ')';
        } elseif (!count($slugs) && count($galleries)) {
            // Searching by gallery_id
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images '
                   . 'WHERE gallery_id IN ('
                   . str_repeat('?, ', count($galleries) - 1) . '?) ';
        } elseif (count($slugs)) {
            // Searching by gallery_slug so we need to join the share table
            $sql = 'SELECT ' . $this->_getImageFields() . ' FROM ansel_images LEFT JOIN '
                . $this->_shares->getTable() . ' ON ansel_images.gallery_id = '
                . $this->_shares->getTable() . '.share_id ' . 'WHERE attribute_slug IN ('
                . str_repeat('?, ', count($slugs) - 1) . '?) ';
        } else {
            return array();
        }

        $sql .= ' ORDER BY image_uploaded_date DESC';
        if ($limit > 0) {
            $GLOBALS['ansel_db']->setLimit((int)$limit);
        }
        $query = $this->_db->prepare($sql);
        if ($query instanceof PEAR_Error) {
           throw new Ansel_Exception($query);
        }

        if (count($slugs)) {
            $images = $query->execute($slugs);
        } else {
            $images = $query->execute($galleries);
        }
        $query->free();
        if ($images instanceof PEAR_Error) {
            throw new Ansel_Exception($images);
        } elseif ($images->numRows() == 0) {
            return array();
        }

        while ($image = $images->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $image['image_filename'] = Horde_String::convertCharset($image['image_filename'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $image['image_caption'] = Horde_String::convertCharset($image['image_caption'], $GLOBALS['conf']['sql']['charset'], 'UTF-8');
            $results[] = new Ansel_Image($image);
        }
        $images->free();

        return $results;
    }

    /**
     * Check if a gallery exists. Need to do this here instead of Horde_Share
     * since Horde_Share::exists() takes a share_name, not a share_id. We
     * might also be checking by gallery_slug and this is more efficient than
     * a listShares() call for one gallery.
     *
     * @param integer $gallery_id  The gallery id
     * @param string  $slug        The gallery slug
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function galleryExists($gallery_id, $slug = null)
    {
        if (empty($slug)) {
            $results = $this->_db->queryOne(
                'SELECT COUNT(share_id) FROM ' . $this->_shares->getTable()
                . ' WHERE share_id = ' . (int)$gallery_id);
            if ($results instanceof PEAR_Error) {
                throw new Ansel_Exception($results);
            }

            return (bool)$results;
        } else {

            return (bool)$this->slugExists($slug);
        }
    }

   /**
    * Return the count of galleries that the user has specified permissions to
    * and that match any of the requested attributes.
    *
    * @param string  $userid       The user to check access for.
    * @param integer $perm         The level of permissions to require for a
    *                              gallery to return it.
    * @param mixed   $attributes   Restrict the galleries counted to those
    *                              matching $attributes. An array of
    *                              attribute/values pairs or a gallery owner
    *                              username.
    * @param string  $parent       The parent share to start counting at.
    * @param boolean $allLevels    Return all levels, or just the direct
    *                              children of $parent? Defaults to all levels.
    *
    * @return int  The count
    * @throws Ansel_Exception
    */
    public function countGalleries($userid, $perm = Horde_Perms::SHOW, $attributes = null,
                            $parent = null, $allLevels = true)
    {
        static $counts;

        if ($parent instanceof Ansel_Gallery) {
            $parent_id = $parent->getId();
        } else {
            $parent_id = $parent;
        }

        $key = "$userid,$perm,$parent_id,$allLevels" . serialize($attributes);
        if (isset($counts[$key])) {
            return $counts[$key];
        }

        try {
            $count = $this->_shares->countShares($userid, $perm, $attributes,
                                                 $parent, $allLevels);
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
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
    *     (string)sort_by    Attribute to sort by
    *     (integer)direction The direction to sort by [Ansel::SORT_ASCENDING]
    *   </pre>
    *
    * @return array An array of Ansel_Gallery objects
    * @throws Ansel_Exception
    */
    public function listGalleries($params = array())
    {
        try {
            $shares = $this->_shares->listShares($GLOBALS['registry']->getAuth(), $params);
        } catch (Horde_Share_Exception $e) {
            throw new Ansel_Exception($e);
        }

        return $shares;
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
    public function getImageJson($images, $style = null, $full = false,
                                 $image_view = 'mini', $view_links = false)
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
                $galleries[$gallery_id]['gallery'] = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($gallery_id);
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

        if (count($json)) {
            return Horde_Serialize::serialize($json, Horde_Serialize::JSON);
        }

        return '';
    }

    /**
     * Returns a random Ansel_Gallery from a list fitting the search criteria.
     *
     * @see Ansel_Storage::listGalleries()
     */
    public function getRandomGallery($params = array())
    {
        $galleries = $this->listGalleries($params);
        if (!$galleries) {
            return false;
        }
        $gallery_key = array_rand($galleries);

        return $galleries[$gallery_key];
    }

    /**
     * Lists a slice of the image ids in the given gallery.
     *
     * @param integer $gallery_id  The gallery to list from.
     * @param integer $from        The image to start listing.
     * @param integer $count       The numer of images to list.
     * @param mixed $fields        The fields to return (either an array of
     *                             fileds or a single string).
     * @param string $where        A SQL where clause ($gallery_id will be
     *                             ignored if this is non-empty).
     * @param mixed $sort          The field(s) to sort by.
     *
     * @return array  An array of image_ids
     * @throws Ansel_Exception
     */
    public function listImages($gallery_id, $from = 0, $count = 0,
                        $fields = 'image_id', $where = '', $sort = 'image_sort')
    {
        if (is_array($fields)) {
            $field_count = count($fields);
            $fields = implode(', ', $fields);
        } elseif ($fields == '*') {
            // The count is not important, as long as it's > 1
            $field_count = 2;
        } else {
            $field_count = substr_count($fields, ',') + 1;
        }

        if (is_array($sort)) {
            $sort = implode(', ', $sort);
        }

        if (!empty($where)) {
            $query_where = 'WHERE ' . $where;
        } else {
            $query_where = 'WHERE gallery_id = ' . $gallery_id;
        }
        $this->_db->setLimit($count, $from);
        $sql = 'SELECT ' . $fields . ' FROM ansel_images ' . $query_where . ' ORDER BY ' . $sort;
        Horde::logMessage('Query by Ansel_Storage::listImages: ' . $sql, 'DEBUG');
        $results = $this->_db->query($sql);
        if ($results instanceof PEAR_Error) {
            throw new Ansel_Exception($results);
        }
        if ($field_count > 1) {
            return $results->fetchAll(MDB2_FETCHMODE_ASSOC, true, true, false);
        } else {
            return $results->fetchCol();
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
    public function getImagesGeodata($image_ids = array(), $gallery = null)
    {
        if ((!is_array($image_ids) || count($image_ids) == 0) && empty($gallery)) {
            return array();
        }

        if (!empty($gallery)) {
            $where = 'gallery_id = ' . (int)$gallery . ' AND LENGTH(image_latitude) > 0';
        } elseif (count($image_ids) > 0) {
            $where = 'image_id IN(' . implode(',', $image_ids) . ') AND LENGTH(image_latitude) > 0';
        } else {
            return array();
        }

        return $this->listImages(0, 0, 0, array('image_id as id', 'image_id', 'image_latitude', 'image_longitude', 'image_location'), $where);
    }

    /**
     * Get image attribtues from ansel_image_attributes table
     *
     * @param int $image_id  The image id
     *
     * @return array  A image attribute hash
     * @throws Horde_Exception
     */
    public function getImageAttributes($image_id)
    {
        $results = $GLOBALS['ansel_db']->queryAll('SELECT attr_name, attr_value FROM ansel_image_attributes WHERE image_id = ' . (int)$image_id, null, MDB2_FETCHMODE_ASSOC, true);
        if ($results instanceof PEAR_Error) {
            throw new Ansel_Exception($results());
        }

        return $results;
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
        $galleries = $this->listGalleries(array('perm' => Horde_Perms::EDIT,
                                                'attributes' => $user));
        if (empty($galleries)) {
            return array();
        }

        $where = 'gallery_id IN(' . implode(',', array_keys($galleries)) . ') AND LENGTH(image_latitude) > 0 GROUP BY image_latitude, image_longitude';
        return $this->listImages(0, $start, $count, array('image_id as id', 'image_id', 'gallery_id', 'image_latitude', 'image_longitude', 'image_location'), $where, 'image_geotag_date DESC');
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
            $sql .= ' AND image_location LIKE ' . $GLOBALS['ansel_db']->quote("$search%");
        }
        Horde::logMessage(sprintf("SQL QUERY BY Ansel_Storage::searchLocations: %s", $sql), 'DEBUG');
        $results = $this->_db->query($sql);
        if ($results instanceof PEAR_Error) {
            throw new Ansel_Exception($results);
        }

        return $results->fetchAll(MDB2_FETCHMODE_ASSOC, true, true, false);
    }

    /**
     * Set the gallery id for a set of images. Useful for bulk updating images
     * when moving from one gallery to another.
     *
     * @param array $image_ids     An array of image ids
     * @param integer $gallery_id  The gallery id to move the images to.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function setImagesGallery($image_ids, $gallery_id)
    {
        $result = $this->_db->exec('UPDATE ansel_images SET gallery_id = ' . $gallery_id . ' WHERE image_id IN (' . implode(',', $image_ids) . ')');
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Ansel_Exception($result);
        }
    }

    /**
     * Deletes an Ansel_Image from data storage.
     *
     * @param integer $image_id  The image id(s) to remove.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function removeImage($image_id)
    {
        $this->_db->exec('DELETE FROM ansel_images WHERE image_id = ' . (int)$image_id);
        $this->_db->exec('DELETE FROM ansel_image_attributes WHERE image_id = ' . (int)$image_id);
    }

    /**
     * Helper function to get a string of field names
     *
     * @return string
     */
    protected function _getImageFields($alias = '')
    {
        $fields = array('image_id', 'gallery_id', 'image_filename', 'image_type',
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
     *
     * @return void
     */
    public function ensureHash($hash)
    {
        $query = $this->_db->prepare('SELECT COUNT(*) FROM ansel_hashes WHERE style_hash = ?');
        $results = $query->execute($hash);
        if ($results instanceof PEAR_Error) {
            throw new Ansel_Exception($results->getMessage());
        }
        if (!$results->fetchOne()) {
            $query = $this->_db->prepare('INSERT INTO ansel_hashes (style_hash) VALUES(?)');
            $results = $query->execute($hash);
            if ($results instanceof PEAR_Error) {
                throw new Ansel_Exception($results->getMessage());
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
        $hashes = $this->_db->query('SELECT style_hash FROM ansel_hashes;');
    }

}
