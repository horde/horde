<?php
/**
 * Ansel external API interface.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Api extends Horde_Registry_Api
{
    /**
     * Browse through Ansel's gallery tree.
     *
     * @param string $path       The level of the tree to browse.
     * @param array $properties  The item properties to return. Defaults to 'name',
     *                           'icon', and 'browseable'.
     *
     * @return array  The contents of $path
     */
    public function browse($path = '', array $properties = array())
    {
        // Default properties.
        if (!$properties) {
            $properties = array('name', 'icon', 'browseable');
        }

        if (substr($path, 0, 5) == 'ansel') {
            $path = substr($path, 5);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        $storage = $GLOBALS['injector']->getInstance('Ansel_Storage');
        if (empty($path)) {
            $owners = array();
            $galleries = $storage->listGalleries(array('all_levels' => false));
            foreach ($galleries  as $gallery) {
                $owners[$gallery->get('owner') ? $gallery->get('owner') : '-system-'] = true;
            }

            $results = array();
            foreach (array_keys($owners) as $owner) {
                if (in_array('name', $properties)) {
                    $results['ansel/' . $owner]['name'] = $owner;
                }
                if (in_array('icon', $properties)) {
                    $results['ansel/' . $owner]['icon'] = Horde_Themes::img('user.png');
                }
                if (in_array('browseable', $properties)) {
                    $results['ansel/' . $owner]['browseable'] = true;
                }
                if (in_array('contenttype', $properties)) {
                    $results['ansel/' . $owner]['contenttype'] =
                        'httpd/unix-directory';
                }
                if (in_array('contentlength', $properties)) {
                    $results['ansel/' . $owner]['contentlength'] = 0;
                }
                if (in_array('modified', $properties)) {
                    $results['ansel/' . $owner]['modified'] = time();
                }
                if (in_array('created', $properties)) {
                    $results['ansel/' . $owner]['created'] = 0;
                }
            }
            return $results;
        } else {
            if (count($parts) == 1) {
                // This request is for all galleries owned by the requested user.
                $galleries = $storage->listGalleries(
                    array('attributes' => $parts[0],
                          'all_levels' => false));
                $images = array();
            } elseif ($this->galleryExists(null, end($parts))) {
                // This request if for a certain gallery, list all sub-galleries
                // and images.
                $gallery_id = end($parts);
                $galleries = $storage->listGalleries(
                    array('parent' => $gallery_id,
                          'all_levels' => false,
                          'perm' => Horde_Perms::SHOW));
                $images = $this->listImages(
                    $gallery_id,
                    array('perms' => Horde_Perms::SHOW,
                          'view' => 'mini'));

            } elseif (count($parts) > 2 &&
                      $this->galleryExists(null, $parts[count($parts) - 2]) &&
                      ($image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage(end($parts)))) {

                return array(
                    'data' => $image->raw(),
                    'mimetype' => $image->type,
                    'mtime' => $image->uploaded);
            } else {
                throw new Horde_Exception_NotFound(_("File not found."));
            }

            $results = array();
            foreach ($galleries as $gallery) {
                $retpath = 'ansel/' . implode('/', $parts) . '/' . $gallery->id;
                if (in_array('name', $properties)) {
                    $results[$retpath]['name'] = $gallery->get('name');
                }
                if (in_array('displayname', $properties)) {
                    $results[$retpath]['displayname'] = rawurlencode($gallery->get('name'));
                }
                if (in_array('icon', $properties)) {
                    $results[$retpath]['icon'] = Horde_Themes::img('ansel.png');
                }
                if (in_array('browseable', $properties)) {
                    $results[$retpath]['browseable'] = $gallery->hasPermission(
                        $GLOBALS['registry']->getAuth(), Horde_Perms::READ);
                }
                if (in_array('contenttype', $properties)) {
                    $results[$retpath]['contenttype'] = 'httpd/unix-directory';
                }
                if (in_array('contentlength', $properties)) {
                    $results[$retpath]['contentlength'] = 0;
                }
                if (in_array('modified', $properties)) {
                    $results[$retpath]['modified'] = time();
                }
                if (in_array('created', $properties)) {
                    $results[$retpath]['created'] = 0;
                }
            }

            foreach ($images as $imageId => $image) {
                $retpath = 'ansel/' . implode('/', $parts) . '/' . $imageId;
                if (in_array('name', $properties)) {
                    $results[$retpath]['name'] = $image['name'];
                }
                if (in_array('displayname', $properties)) {
                    $results[$retpath]['displayname'] = rawurlencode($image['name']);
                }
                if (in_array('icon', $properties)) {
                    $results[$retpath]['icon'] = Horde::url($image['url'], true);
                }
                if (in_array('browseable', $properties)) {
                    $results[$retpath]['browseable'] = false;
                }
                if (in_array('contenttype', $properties)) {
                    $results[$retpath]['contenttype'] = $image['type'];
                }
                if (in_array('contentlength', $properties)) {
                    $results[$retpath]['contentlength'] = 0;
                }
                if (in_array('modified', $properties)) {
                    $results[$retpath]['modified'] = $image['uploaded'];
                }
                if (in_array('created', $properties)) {
                    $results[$retpath]['created'] = $image['uploaded'];
                }
            }
            return $results;

        }

        throw Horde_Exception_NotFound(_("File not found."), 404);
    }

    /**
     * Saves an image into the gallery tree.
     *
     * @param string $path          The path where to PUT the file.
     * @param string $content       The file content.
     * @param string $content_type  The file's content type.
     *
     * @return array  The event UIDs.
     * @throws Horde_Exception_PermissionDenied
     * @throws Horde_Exception_NotFound
     */
    public function put($path, $content, $content_type)
    {
        if (substr($path, 0, 5) == 'ansel') {
            $path = substr($path, 9);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (count($parts) < 3) {
            throw new Horde_Exception_NotFound("Gallery does not exist");
        }
        $image_name = array_pop($parts);
        $gallery_id = end($parts);
        if (!$GLOBALS['injector']->getInstance('Ansel_Storage')->galleryExists($gallery_id)) {
            throw new Horde_Exception_NotFound("Gallery does not exist");
        }
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($gallery_id);
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied(_("Access denied adding photos to \"%s\"."));
        }

        return $gallery->addImage(array(
            'image_type' => $content_type,
            'image_filename' => $image_name,
            'image_caption' => '',
            'data' => $content));
    }

    /**
     * Callback for Agora comments.
     *
     * @param integer $image_id  Image id to check
     *
     * @return mixed Image filename on success | false on failure
     */
    public function commentCallback($image_id)
    {
        if (!$GLOBALS['conf']['comments']['allow']) {
            return false;
        }

        try {
            if (!($image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($image_id))) {
                return false;
            }
        } catch (Ansel_Exception $e) {
            return false;
        }

        return $image->filename;
    }

    /**
     * Checks if applications allows comments
     *
     * @return boolean
     */
    public function hasComments()
    {
        if (($GLOBALS['conf']['comments']['allow'] == 'all' ||
            ($GLOBALS['conf']['comments']['allow'] == 'authenticated' &&
            $GLOBALS['registry']->getAuth()))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns decoded image data
     *
     * @param string $data         The image data
     * @param string $encoding     The encoding type for the image data.
     *                             (none, base64, or binhex)
     * @param string $compression  The compression type for the image data.
     *                             (none, gzip, or lzf)
     * @param boolean $upload      Process direction (true of encode/compress or false if decode/decompress)
     *
     * @return string  The decoded/encoded image data
     */
    protected function _getImageData($data, $encoding = 'none', $compression = 'none', $upload = true)
    {
        switch ($encoding) {
        case 'base64':
            $data = $upload ? base64_decode($data) : base64_encode($data);
            break;

        case 'binhex':
            $data = $upload ? pack('H*', $data) : unpack('H*', $data);
        }

        switch ($compression) {
        case 'gzip':
            if (Horde_Util::loadExtension('zlib')) {
                return $upload ? gzuncompress($data) : gzcompress($data);
            }
            break;

        case 'lzf':
            if (Horde_Util::loadExtension('lzf')) {
                return $upload ? lzf_decompress($data) : lzf_compress($data);
            }
            break;

        default:
            return $data;
        }
    }

    /**
     * Stores an image in a gallery and returns gallery and image data.
     *
     * @param integer $gallery_id  The gallery id to add the image to.
     * @param array $image         Image data array. This can either be the
     *                             return from Horde_Form_Type_image:: or an
     *                             array with the following four fields:
     *                             'filename', 'description', 'data', 'type' and
     *                             optionally 'tags'
     *
     * @param array $params  An array of additional parameters:
     * <pre>
     *   (string)slug         If set, use this as the gallery slug
     *                        (ignores $gallery_id)
     *   (string)scope        The scope to use, if not the default.
     *   (boolean)default     Set this as the key gallery image.
     *   (array)gallery_data  Any gallery parameters to change at this time.
     *   (string)encoding     The encoding type for the image data (base64 or binhex)
     *   (string)compression  The compression type for image data (gzip,lzf)
     *   (boolean)skiphook    Don't call the postupload hook(s).
     * </pre>
     *
     * @return array  An array of image/gallery data
     * @throws InvalidArgumentException
     * @throws Horde_Exception_PermissionDenied
     */
    public function saveImage($gallery_id, array $image, array $params = array())
    {
        // Set application scope
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')->set('scope', $params['scope']);
        }

        // Build image upload structure
        if (isset($image['filename']) &&
            isset($image['description']) &&
            isset($image['data']) &&
            isset($image['type'])) {
                Horde::logMessage(sprintf("Receiving image %s in saveImage() with a raw filesize of %i", $image['filename'], strlen($image['data'])), 'DEBUG');
                $image_data = array(
                    'image_filename' => $image['filename'],
                    'image_caption' => $image['description'],
                    'image_type' => $image['type'],
                    'data' => $this->_getImageData($image['data'], (empty($params['encoding']) ? 'none' : $params['encoding']), (empty($params['compression']) ? 'none' : $params['compression']), true));
        }

        // Validate the image data and other requirements
        if (empty($image_data) && getimagesize($image['file']) === false) {
            throw new InvalidArgumentException(_("The file you uploaded does not appear to be a valid photo."));
        }
        if (empty($params['slug']) && empty($gallery_id)) {
            throw new InvalidArgumentException(_("A gallery to add this photo to is required."));
        }
        if (!empty($params['slug'])) {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGalleryBySlug($params['slug']);
        } elseif ($GLOBALS['injector']->getInstance('Ansel_Storage')->galleryExists($gallery_id)) {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($gallery_id);
        }

        // Check perms for requested gallery
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied(sprintf(_("Access denied adding photos to \"%s\"."), $gallery->get('name')));
        }

        // Changing any values while we are at it?
        if (!empty($params['gallery_data'])) {
            foreach ($params['gallery_data'] as $key => $value) {
                $gallery->set($key, $value);
            }
            $gallery->save();
        }

        if (empty($image_data)) {
            $image_data = array(
                'image_filename' => $image['name'],
                'image_caption' => $image['name'],
                'image_type' => $image['name']['type'],
                'data' => file_get_contents($image['file']),
            );
        }

        if (isset($image['tags']) && is_array($image['tags']) && count($image['tags'])) {
            $image_data['tags'] = $image['tags'];
        }
        $image_id = $gallery->addImage($image_data, !empty($params['default']));

        // Call the postupload hook if needed
        if (empty($params['skiphook'])) {
            $this->postBatchUpload($image_id);
        }

        return array(
            'image_id'   => (int)$image_id,
            'gallery_id' => (int)$gallery->id,
            'gallery_slug' => $gallery->get('slug'),
            'image_count' => (int)$gallery->countImages());
    }

    /**
     * Notify Ansel that a group of images has just been uploaded. Used for when
     * the postupload hook should be called with a group of recently
     * uploaded images, as opposed to calling it once after each image is saved.
     *
     * @param array $image_ids  An array of image ids.
     */
    public function postBatchUpload(array $image_ids)
    {
        try {
            Horde::callHook('postupload', array($image_ids), 'ansel');
        } catch (Horde_Exception_HookNotSet $e) {}
    }

    /**
     * Removes an image from a gallery.
     *
     * @param integer $gallery_id The id of gallery.
     * @param string $image_id    The id of image to remove.
     * @param array $params       Additional parameters:
     * <pre>
     *   (string)scope  The scope to use, if not the default.
     * </pre>
     *
     * @throws Horde_Exception_PermissionDenied
     */
    public function removeImage($gallery_id, $image_id, array $params = array())
    {
        // Check global Ansel permissions
        if (!$GLOBALS['injector']->getInstance('Horde_Perms')->getPermissions('ansel', $GLOBALS['registry']->getAuth())) {
            throw new Horde_Exception_PermissionDenied(_("Access denied deleting galleries."));
        }

        // Set a custom scope, if needed
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')->set('scope', $params['scope']);
        }

        $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($image_id);
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($image->gallery);
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
            throw new Horde_Exception_PermissionDenied(sprintf(_("Access denied deleting photos from \"%s\"."), $gallery->get('name')));
        }

        $gallery->removeImage($image);
    }

    /**
     * Add a new gallery to any application scope.
     *
     * @param array $attributes  The gallery attributes
     * @param array $params      Additional (optional) parameters:
     *<pre>
     *    (string)scope    The scope to use, if not the default.
     *    (array)perm      An array of permission data if Ansel's defaults are
     *                     not desired. Takes an array like:
     *                        array('guest' => Horde_Perms::SHOW | Horde_Perms::READ,
     *                              'default' => Horde_Perms::SHOW | Horde_Perms::READ);
     *    (integer)parent  The gallery id of the parent gallery, if not a top level gallery.
     *</pre>
     *
     * @return integer  The gallery id of the new gallery
     * @throws Horde_Exception_PermissionDenied
     */
    public function createGallery($attributes, array $params = array())
    {
        if (!($GLOBALS['registry']->isAdmin() ||
            (!$GLOBALS['injector']->getInstance('Horde_Perms')->exists('ansel') && $GLOBALS['registry']->getAuth()) ||
            $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('ansel', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT))) {

            throw new Horde_Exception_PermissionDenied(_("Access denied creating new galleries."));
        }

        // Custom scope?
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params['scope']);
        }

        // Non-default perms?
        if (!empty($params['perm'])) {
            // The name is inconsequential; it is only used as a container to
            // represent permissions when passed to the Ansel backend.
            $permobj = new Horde_Perms_Permission('');
            $permobj->data = $perm;
        } else {
            $permobj = null;
        }

        // Create the gallery
        $gallery = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->createGallery(
                $attributes,
                $permobj,
                (!empty($params['parent']) ? $params['parent'] : null));

        return $gallery->id;
    }

    /**
     * Removes a gallery and its images.
     *
     * @param integer $gallery_id  The id of gallery.
     * @param array $params        Any additional, optional, parameters:
     *  <pre>
     *    (string)scope  the scope to use, if not the default
     *  </pre>
     *
     * @throws Ansel_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function removeGallery($gallery_id, array $params = array())
    {
        // Check global Ansel permissions
        if (!$GLOBALS['injector']->getInstance('Horde_Perms')->getPermissions('ansel', $GLOBALS['registry']->getAuth())) {
            throw new Horde_Exception_PermissionDenied(_("Access denied deleting galleries."));
        }

        // Custom scope, if needed
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params['scope']);
        }

        // Get, and check perms on the gallery
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($gallery_id);
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
            throw new Horde_Exception_PermissionDenied(sprintf(_("Access denied deleting gallery \"%s\"."), $gallery->get('name')));
        } else {
            $GLOBALS['injector']
                ->getInstance('Ansel_Storage')
                ->removeGallery($gallery);
        }
    }

    /**
     * Returns the number of images in a gallery.
     *
     * @param integer $gallery_id   The gallery id.
     * @param array $params         Array of optional parameters:
     *<pre>
     *    (string)scope  Scope to use, if not the default.
     *    (string)slug   If set, ignore gallery_id and use this as the slug.
     * </pre>
     *
     * @return integer  The number of images in the gallery.
     * @throws Ansel_Exception
     */
    public function count($gallery_id = null, array $params = array())
    {
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')->set('scope', $params['scope']);
        }

        try {
            if (!empty($params['slug'])) {
                $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGalleryBySlug($params['slug']);
            } else {
                $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($gallery_id);
            }
            return (int)$gallery->countImages();
        } catch (Ansel_Exception $e) {
            return 0;
        }
    }

    /**
     * Returns the id of the specified gallery's key image.
     *
     * @param integer $gallery_id  The gallery id.
     * @param array $params        Additional parameters:
     *<pre>
     *  (string)scope   Application scope, if not the default
     *  (string)style   A named style to use, if not ansel_default
     *  (string)slug    Ignore gallery_id, and use this as the slug
     *</pre>
     *
     * @return integer  The key image id.
     */
    public function getGalleryKeyImage($gallery_id, array $params = array())
    {
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params['scope']);
        }

        if (!empty($params['slug'])) {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')
                ->getGalleryBySlug($params['slug']);
        } else {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')
                ->getGallery($gallery_id);
        }

        $style = empty($params['style']) ?
            Ansel::getStyleDefinition('ansel_default') :
            Ansel::getStyleDefinition($params['style']);

        return $gallery->getKeyImage($style);
    }

    /**
     * Returns the URL to the specified image.
     *
     * @param integer $image_id  The id of the image.
     * @param array $params      Additional optional parameters:
     *  <pre>
     *    (string)scope  The application scope, if not the default.
     *    (string)view   The image view type to return (screen, thumb, etc...)
     *    (string)full   Return a fully qualified path?
     *    (string)style  Use this gallery style instead of ansel_default.
     *  </pre>
     *
     * @return string  The image path.
     */
    public function getImageUrl($image_id, array $params = array())
    {
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params['scope']);
        }

        $style = empty($params['style']) ?
            Ansel::getStyleDefinition('ansel_default') :
            Ansel::getStyleDefinition($params['style']);

        return (string)Ansel::getImageUrl(
            $image_id,
            empty($params['view']) ? 'screen': $params['view'],
            empty($params['full']) ? false : $params['full'],
            $style);
    }

    /**
     * Returns raw image data in specified encoding/compression format.
     *
     * @TODO: See about using a stream
     *
     * @param integer $image_id  The id of the image.
     * @param array $params      Optional parameters:
     *<pre>
     *  (string)scope        Application scope, if not default.
     *  (string)view         The image view type to return (screen, thumb etc...)
     *  (string)style        Force the use of this gallery style
     *  (string)encoding     Encoding type (base64, binhex)
     *  (string)compression  Compression type (gzip, lzf)
     *</pre>
     *
     * @return string  The raw image data.
     * @throws Horde_Exception_Permission_Denied
     * @throws Ansel_Exception
     */
    public function getImageContent($image_id, array $params = array())
    {
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params['scope']);
        }

        // Get image and gallery
        $image = $GLOBALS['injector']->getInstance('Ansel_Storage')
            ->getImage($image_id);
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')
            ->getGallery($image->gallery);

        // Check age and password
        if ($gallery->hasPasswd() || !$gallery->isOldEnough()) {
            throw new Horde_Exception_PermissionDenied(
                _("Locked galleries are not viewable via the api."));
        }

        if ($view == 'full') {
            // Check permissions for full view
            if (!$gallery->canDownload()) {
                throw new Horde_Exception_PermissionDenied(
                    sprintf(_("Access denied downloading full sized photos from \"%s\"."), $gallery->get('name')));
            }

            // Try reading the data
            try {
                $data = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_Vfs')
                    ->create('images')
                    ->read($image->getVFSPath('full'), $image->getVFSName('full'));
            } catch (Horde_Vfs_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                throw new Ansel_Exception($e->getMessage());
            }
        } else {
            if (!empty($params['style'])) {
                $params['style'] = Ansel::getStyleDefinition($style);
            } else {
                $params['style'] = null;
            }
            $image->load($view, $params['style']);
            $data = $image->_image->raw();
        }

        return $this->_getImageData($data, $encoding, $compression, false);
    }

    /**
     * Returns a list of all galleries.
     *
     * @param array $params  Optional parameters:
     *<pre>
     *  (string)scope       The application scope, if not default.
     *  (integer)perm       The permissions filter to use [Horde_Perms::SHOW]
     *  (mixed)attributes   Restrict the galleries returned to those matching
     *                      the filters. Can be an array of attribute/values
     *                      pairs or a gallery owner username.
     *  (integer)parent     The parent share to start listing at.
     *  (boolean)all_levels If set, return all levels below parent, not just
     *                      direct children [TRUE]
     *  (integer)from       The gallery to start listing at.
     *  (integer)count      The number of galleries to return.
     *  (string)sort_by     Attribute to sort by
     *  (integer)direction  The direction to sort by [Ansel::SORT_ASCENDING]
     *  (array)tags        An array of tags to limit results by.
     *</pre>
     *
     * @return array  An array of gallery information.
     */
    public function listGalleries(array $params = array())
    {
        // If no scope is given use Ansel's default
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params['scope']);
        }
        $galleries = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->listGalleries($params);
        $return = array();
        foreach ($galleries as $gallery) {
            $return[] = array_merge(
                $gallery->toArray(),
                array('crumbs' => $gallery->getGalleryCrumbData()));
        }

        return $return;
    }

    /**
     * Returns an array of gallery information.
     *
     * @param array $ids   An array of gallery ids.
     * @param string $app  Application scope to use, if not the default.
     * @param array $slugs An array of gallery slugs (ignore $ids).
     *
     * @return array An array of gallery data arrays
     */
    public function getGalleries(array $ids, $app = null, array $slugs = array())
    {
        if (!is_null($app)) {
            $GLOBALS['injector']->getInstance('Ansel_Config')->set('scope', $app);
        }

        if (count($slugs)) {
            $results = $GLOBALS['injector']->getInstance('Ansel_Storage')
                ->getGalleriesBySlugs($slugs);
        } else {
            $results = $GLOBALS['injector']->getInstance('Ansel_Storage')
                ->getGalleries($ids);
        }

        // We can't just return the results of the getGalleries call - we need
        // to build the non-object return structure.
        $galleries = array();
        foreach ($results as $gallery) {
            $galleries[$gallery->id] = array_merge(
                $gallery->data,
                array('crumbs' => $gallery->getGalleryCrumbData()));
        }

        return $galleries;
    }

    /**
     * Returns a 'select' menu from the list of galleries created by
     * listGalleries().
     *
     * @param array $params  Optional parameters:
     *<pre>
     *  (string)scope      Application scope, if not default.
     *  (integer)selected  The gallery_id of the gallery that is selected
     *  (integer)perm      The permissions filter to use [Horde_Perms::SHOW]
     *  (mixed)filter      Restrict the galleries returned to those matching
     *                     the filters. Can be an array of attribute/values
     *                     pairs or a gallery owner username.
     *  (integer)parent    The parent share to start listing at.
     *  (integer)from      The gallery to start listing at.
     *  (integer)count     The number of galleries to return.
     *  (integer)ignore    An Ansel_Gallery id to ignore when building the tree.
     *</pre>
     */
    public function selectGalleries(array $params = array())
    {
        if (!empty($params['scope'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params['scope']);
            unset($params['scope']);
        }

        return Ansel::selectGalleries($params);
    }

    /**
     * Returns a list of all images in a gallery.
     *
     * The return has the URL because in a lot of cases you'll want the url
     * also. Using api call getImageURL results in a lot of overhead when
     * e.g. generating a select list.
     *
     * @param integer $gallery_id  Gallery id to get images from.
     * @param array $params        Additional parameters:
     *<pre>
     *  (string)app          Application scope to use [ansel].
     *  (string)view         View size to generate URLs for [thumb].
     *  (boolean)full        Return a full URL [false].
     *  (integer)from        Start image.
     *  (integer)limit       Max count of images to return.
     *  (string)style        Use this gallery style.
     *  (string)slug         Gallery slug (ignore gallery_id).
     *</pre>
     *
     * @return array  Hash of image data (see below) keyed by image_id.
     *<pre>
     *  name
     *  caption
     *  type
     *  uploaded
     *  original_date
     *  url
     *</pre>
     */
    public function listImages($gallery_id, array $params = array())
    {
        $params = new Horde_Support_Array($params);
        if ($params->app) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params->app);
        }
        $storage = $GLOBALS['injector']->getInstance('Ansel_Storage');
        if ($params->slug) {
            $gallery = $storage->getGalleryBySlug($params->slug);
        } else {
            $gallery = $storage->getGallery($gallery_id);
        }
        $images = $gallery->listImages($params->get('from', 0), $params->get('limit', 0));
        if ($params->style) {
            $params->style = Ansel::getStyleDefinition($params->style);
        } else {
            $params->style = $gallery->getStyle();
        }

        $counter = 0;
        $imagelist = array();
        foreach ($images as $id) {
            $image = $storage->getImage($id);
            $imagelist[$id]['name'] = $image->filename;
            $imagelist[$id]['caption'] = $image->caption;
            $imagelist[$id]['type'] = $image->type;
            $imagelist[$id]['uploaded'] = $image->uploaded;
            $imagelist[$id]['original_date'] = $image->originalDate;
            $imagelist[$id]['url'] = Ansel::getImageUrl(
                $id, $params->get('view', 'thumb'), $params->get('full', false), $params->style);
            if ($params->app && $GLOBALS['conf']['vfs']['src'] != 'direct') {
                $imagelist[$id]['url']->add('app', $params->app);
            }
            $imagelist[$id]['url'] = $imagelist[$id]['url']->toString();
        }

        return $imagelist;
    }

    /**
     * Return a list of recently added images
     *
     * @param array $params  Parameter (optionally) containing:
     *<pre>
     *   (string)app       Application used if null then use default.
     *   (array)galleries  An array of gallery ids to check.  If left empty,
     *                     will search all galleries with the given
     *                     permissions for the current user.
     *   (string)view      The type of image view to return.
     *   (boolean)full     Return a full URL if this is true.
     *   (integer)limit    The maximum number of images to return.
     *   (string)style     Force the use of this gallery style
     *   (array)slugs      An array of gallery slugs
     *
     * @return array  A hash of image information arrays, keyed by image_id:
     * @see Ansel_Api::getImages
     */
    public function getRecentImages(array $params = array())
    {
        $params = new Horde_Support_Array($params);

        if ($params->app) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params->app);
        }
        $images = $GLOBALS['injector']->getInstance('Ansel_Storage')
            ->getRecentImages(
                $params->get('galleries', array()),
                $params->get('limit', 10),
                $params->get('slugs', array()));
        $imagelist = array();
        if ($params->style) {
            $params->style = Ansel::getStyleDefinition($params->style);
        }
        foreach ($images as $image) {
            $id = $image->id;
            $imagelist[$id]['id'] = $id;
            $imagelist[$id]['name'] = $image->filename;
            $imagelist[$id]['url'] = Ansel::getImageUrl(
                $id,
                $params->get('view', 'screen'),
                $params->get('full', false),
                $params->style);
            $imagelist[$id]['caption'] = $image->caption;
            $imagelist[$id]['filename'] = $image->filename;
            $imagelist[$id]['gallery'] = $image->gallery;
            $imagelist[$id]['uploaded'] = $image->uploaded;
            $imagelist[$id]['original_date'] = $image->originalDate;

            if ($params->app && $GLOBALS['conf']['vfs']['src'] != 'direct') {
                $imagelist[$id]['url']->add('app', $params->app);
            }
        }
        return $imagelist;

    }

    /**
     * Counts the number of galleries.
     *
     * @param array $params  Parameter array containing the following optional:
     *<pre>
     *  (string)app         Application scope to use, if not the default.
     *  (integer)perm       The level of permissions to require for a gallery
     *                      to return it.
     *  (mixed)attributes   Restrict the galleries counted to those matching
     *                      attributes. An array of attribute/value pairs or
     *                      a gallery owner username.
     *  (integer)parent     The parent gallery id to start searching at.
     *  (boolean)all_levels  Return all levels, or just the direct children of
     *                      $parent?
     *
     * @return integer  Returns the number of matching galleries.
     */
    public function countGalleries(array $params = array())
    {
        if (!empty($params['app'])) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $params['app']);
            unset($params['app']);
        }

        return $GLOBALS['injector']->getInstance('Ansel_Storage')
            ->countGalleries(
                $GLOBALS['registry']->getAuth(),
                $params);
    }

    /**
     * Retrieve the list of used tag_names, tag_ids and the total number
     * of resources that are linked to that tag.
     *
     * @param array $tags  An optional array of tag_ids. If omitted, all tags
     *                     will be included.
     *
     * @return array  An array containing tag_name, and total
     */
    public function listTagInfo($tags = null, $user = null)
    {
        return $GLOBALS['injector']->getInstance('Ansel_Tagger')->getTagInfo($tags, 500, null, $user);
    }

    /**
     * SearchTags API:
     * Returns an application-agnostic array (useful for when doing a tag search
     * across multiple applications)
     *
     * The 'raw' results array can be returned instead by setting $raw = true.
     *
     * @param array $names           An array of tag_names to search for.
     * @param integer $max           The maximum number of resources to return.
     * @param integer $from          The number of the resource to start with.
     * @param string $resource_type  The resource type [gallery, image, '']
     * @param string $user           Restrict results to resources owned by $user.
     * @param boolean $raw           Return the raw data?
     * @param string $app            Application scope to use, if not the default.
     *
     * @return array An array of results:
     * <pre>
     *  'title'    - The title for this resource.
     *  'desc'     - A terse description of this resource.
     *  'view_url' - The URL to view this resource.
     *  'app'      - The Horde application this resource belongs to.
     * </pre>
     */
    public function searchTags($names, $max = 10, $from = 0,
                               $resource_type = '', $user = null, $raw = false,
                               $app = 'ansel')
    {
        $GLOBALS['injector']->getInstance('Ansel_Config')->set('scope', $app);
        $results = $GLOBALS['injector']
            ->getInstance('Ansel_Tagger')
            ->search(
                $names,
                array('type' => $resource_type, 'user' => $user));

        // Check for error or if we requested the raw data array.
        if ($raw) {
            return $results;
        }

        $return = array();
        if (!empty($results['images'])) {
            foreach ($results['images'] as $image_id) {
                $image = $GLOBALS['injector']
                    ->getInstance('Ansel_Storage')
                    ->getImage($image_id);
                $g = $GLOBALS['injector']
                    ->getInstance('Ansel_Storage')
                    ->getGallery($image->gallery);
                $view_url = Ansel::getUrlFor('view',
                    array('gallery' => $image->gallery,
                    'image' => $image_id,
                    'view' => 'Image'),
                    true);
                $gurl = Ansel::getUrlFor('view', array('view' => 'Gallery', 'gallery' => $image->gallery));
                $return[] = array(
                    'title' => $image->filename,
                    'desc'=> $image->caption . ' '. _("from") . ' ' . $gurl->link() . $g->get('name') . '</a>',
                    'view_url' => $view_url,
                    'app' => $app,
                    'icon' => Ansel::getImageUrl($image_id, 'mini'));
            }
        }

        if (!empty($results['galleries'])) {
            foreach ($results['galleries'] as $gallery) {
                $gal = $GLOBALS['injector']
                    ->getInstance('Ansel_Storage')
                    ->getGallery($gallery);
                $view_url = Horde::url('view.php')
                    ->add(
                        array('gallery' => $gallery,
                              'view' => 'Gallery'));
                $gurl = Ansel::getUrlFor('view', array('view' => 'Gallery', 'gallery' => $gallery));
                $return[] = array(
                    'desc' => $gurl->link() . $gal->get('name') . '</a>',
                    'view_url' => $view_url,
                    'app' => $app,
                    'icon' => Ansel::getImageUrl($gal->getKeyImage(), 'mini'));
            }
        }

        return $return;
    }

    /**
     * Checks if the gallery exists
     *
     * @param integer $gallery_id  The gallery id
     * @param string $slug         The gallery slug
     * @param string $app          Application scope to use, if not the default.
     *
     * @return boolean
     */
    public function galleryExists($gallery_id, $slug = '', $app = null)
    {
        if (!is_null($app)) {
            $GLOBALS['injector']->getInstance('Ansel_Config')
                ->set('scope', $app);
        }

        return $GLOBALS['injector']->getInstance('Ansel_Storage')
            ->galleryExists($gallery_id, $slug);
    }

    /**
     * Get a list of all pre-configured styles.
     *
     * @return hash of style definitions.
     */
    public function getGalleryStyles()
    {
        return $GLOBALS['injector']->getInstance('Ansel_Styles');
    }

    /**
     * Renders a gallery view
     *
     * @param array $params         Any parameters that the view might need.
     *                              @see Ansel_View_* classes for descriptions of
     *                              available parameters to use here.
     * @param string $app           Application scope to use, if not the default.
     * @param string $view          The generic type of view we want.
     *                              (Gallery, Image, List, Embedded)
     *
     * @return array  An array containing 'html' and 'crumbs' keys.
     */
    public function renderView($params = array(), $app = null, $view = 'Gallery')
    {
        if (!is_null($app)) {
            $GLOBALS['injector']->getInstance('Ansel_Config')->set('scope', $app);
        }
        $classname = 'Ansel_View_' . basename($view);
        $params['api'] = true;
        $params['view'] = $view;
        $trail = array();
        $return = array();
        try {
            $view = new $classname($params);
        } catch (Horde_Exception $e) {
            $return['html'] = $e->getMessage();
            $return['crumbs'] = array();
            return $return;
        }
        $return['html'] = $view->html();
        if ($params['view'] == 'Gallery' || $params['view'] == 'Image') {
            $trail = $view->getGalleryCrumbData();
        }
        $return['crumbs'] = $trail;

        return $return;
    }

}
