<?php
/**
 * Ansel external API interface.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

$_services['browse'] = array(
    'args' => array('path' => 'string'),
    'type' => '{urn:horde}hashHash',
);

$_services['put'] = array(
    'args' => array('path' => 'string', 'content' => 'string', 'content_type' => 'string'),
    'type' => '{urn:horde}stringArray',
);

// $_services['path_delete'] = array(
//     'args' => array('path' => 'string'),
//     'type' => 'boolean',
// );

$_services['commentCallback'] = array(
    'args' => array('image_id' => 'string'),
    'type' => 'string'
);

$_services['hasComments'] = array(
    'args' => array(),
    'type' => 'boolean'
);

$_services['saveImage'] = array(
    'args' => array('app'          => 'string',
                    'gallery_id'   => 'string',
                    'image'        => '{urn:horde}hashHash',
                    'default'      => 'boolean',
                    'gallery_data' => '{urn:horde}hashHash',
                    'encoding'     => 'string',
                    'slug'         => 'string',
                    'compression'  => 'string',
                    'skiphook'     => 'boolean'),
    'type' => '{urn:horde}stringArray'
);

$_services['postBatchUpload'] = array(
    'args' => array('image_ids' => '{urn:horde}hash'),
    'type' => 'int'
);

$_services['createGallery'] = array(
    'args' => array('app'        => 'string',
                    'attributes' => '{urn:horde}hashHash',
                    'perm'       => '{urn:horde}hashHash'),
    'type' => 'int'
);

$_services['removeImage'] = array(
    'args' => array('app'        => 'string',
                    'gallery_id' => 'integer',
                    'image_id'   => 'integer'),
    'type' => 'int'
);

$_services['removeGallery'] = array(
    'args' => array('app'        => 'string',
                    'gallery_id' => 'integer'),
    'type' => 'int'
);

$_services['getImageUrl'] = array(
    'args' => array('app'        => 'string',
                    'image_id'   => 'integer',
                    'view'       => 'string',
                    'full'       => 'boolean',
                    'style'      => 'string'),
    'type' => 'string'
);

$_services['getImageContent'] = array(
    'args' => array('image_id'   => 'integer',
                    'view'       => 'string',
                    'style'      => 'string',
                    'app'        => 'string'),
    'type' => 'string'
);

$_services['count'] = array(
    'args' => array('app'        => 'string',
                    'gallery_id' => 'integer'),
    'type' => 'int'
);

$_services['getDefaultImage'] = array(
    'args' => array('app'        => 'string',
                    'gallery_id' => 'integer',
                    'style'      => 'string'),
    'type' => 'string'
);

$_services['listGalleries'] = array(
    'args' => array('app'        => 'string',
                    'perm'       => 'integer',
                    'parent'     => 'string',
                    'allLevels'  => 'string',
                    'from'       => 'integer',
                    'count'      => 'integer'),
    'type' => 'string'
);

$_services['getGalleries'] = array(
    'args' => array('ids' => '{urn:horde}hash',
                    'app' => 'string'),
    'type' => '{urn:horde}hash'
);

$_services['selectGalleries'] = array(
    'args' => array('app'        => 'string',
                    'perm'       => 'integer',
                    'parent'     => 'string',
                    'allLevels'  => 'string',
                    'from'       => 'integer',
                    'count'      => 'integer'),
    'type' => 'string'
);

$_services['listImages'] = array(
    'args' => array('app'        => 'string',
                    'gallery_id' => 'integer',
                    'perm'       => 'integer',
                    'view'       => 'string',
                    'full'       => 'boolean',
                    'from'       => 'integer',
                    'count'      => 'integer',
                    'style'      => 'string'),
    'type' => 'string'
);

$_services['getRecentImages'] = array(
    'args' => array('app' => 'string',
                    'galleries' => '{urn:horde}hash',
                    'perms' => 'integer',
                    'view' => 'string',
                    'full' => 'boolean',
                    'limit' => 'integer',
                    'style' => 'string',
                    'slugs' => '{urn:horde}hashHash'),
    'type' => '{urn:horde}hash'
);

$_services['countGalleries'] = array(
    'args' => array('app'        => 'string',
                    'perm'       => 'string',
                    'attributes' => '{urn:horde}hash',
                    'parent'     => 'string',
                    'allLevels'  => 'boolean'),
    'type' => 'int'
);

$_services['listTagInfo'] = array(
    'args' => array('tags' => '{urn:horde}stringArray'),
    'type' => '{urn:horde}hash'
);

$_services['searchTags'] = array(
    'args' => array('tags' => '{urn:horde}stringArray',
                    'resource_type' => 'string',
                    'count' => 'int',
                    'user' => 'string'),
    'type' => '{urn:horde}hash'
);

$_services['galleryExists'] = array(
    'args' => array('app' => 'string',
                    'gallery_name' => 'string'),
    'type' => 'boolean'
);

$_services['renderView'] = array(
    'args' => array('parameters' => '{urn:horde]stringArray',
                    'app' => 'string',
                    'view' => 'string'),
    'type' => 'string'
);

$_services['getGalleryStyles'] = array(
    'args' => array(),
    'type' => '{urn:horde}hash');

/**
 * Returns a list of available permissions.
 *
 * @return array  An array describing all available permissions.
 */
function _ansel_perms()
{
    $perms = array();
    $perms['tree']['ansel']['admin'] = false;
    $perms['title']['ansel:admin'] = _("Administrators");

    return $perms;
}

/**
 * Browse through Ansel's gallery tree.
 *
 * @param string $path       The level of the tree to browse.
 * @param array $properties  The item properties to return. Defaults to 'name',
 *                           'icon', and 'browseable'.
 *
 * @return array  The contents of $path
 */
function _ansel_browse($path = '', $properties = array())
{
    require_once dirname(__FILE__) . '/base.php';

    // Default properties.
    if (!$properties) {
        $properties = array('name', 'icon', 'browseable');
    }

    if (substr($path, 0, 5) == 'ansel') {
        $path = substr($path, 5);
    }
    $path = trim($path, '/');
    $parts = explode('/', $path);

    if (empty($path)) {
        $owners = array();
        $galleries = $GLOBALS['ansel_storage']->listGalleries(PERMS_SHOW, null, null, false);
        foreach ($galleries  as $gallery) {
            $owners[$gallery->data['share_owner']] = true;
        }

        $results = array();
        foreach (array_keys($owners) as $owner) {
            if (in_array('name', $properties)) {
                $results['ansel/' . $owner]['name'] = $owner;
            }
            if (in_array('icon', $properties)) {
                $results['ansel/' . $owner]['icon'] =
                    $registry->getImageDir('horde') . '/user.png';
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
            $galleries = $GLOBALS['ansel_storage']->listGalleries(
                PERMS_SHOW, $parts[0], null, false);
            $images = array();
        } elseif (_ansel_galleryExists(null, end($parts))) {
            // This request if for a certain gallery, list all sub-galleries
            // and images.
            $gallery_id = end($parts);
            $galleries = $GLOBALS['ansel_storage']->getGalleries(
                array($gallery_id));
            if (!isset($galleries[$gallery_id]) ||
                !$galleries[$gallery_id]->hasPermission(Horde_Auth::getAuth(),
                                                        PERMS_READ)) {
                return PEAR::raiseError(_("Invalid gallery specified."), 404);
            }
            $galleries = $GLOBALS['ansel_storage']->listGalleries(
                PERMS_SHOW, null, $gallery_id, false);

            $images = _ansel_listImages(null, $gallery_id, PERMS_SHOW, 'mini');
        } elseif (count($parts) > 2 &&
                  _ansel_galleryExists(null, $parts[count($parts) - 2]) &&
                  !is_a($image = $GLOBALS['ansel_storage']->getImage(end($parts)), 'PEAR_Error')) {
            return array('data' => $image->raw(),
                         'mimetype' => $image->type,
                         'mtime' => $image->uploaded);
        } else {
            return PEAR::raiseError(_("File not found."), 404);
        }

        $results = array();
        foreach ($galleries as $galleryId => $gallery) {
            $retpath = 'ansel/' . implode('/', $parts) . '/' . $galleryId;
            if (in_array('name', $properties)) {
                $results[$retpath]['name'] = $gallery->data['attribute_name'];
            }
            if (in_array('displayname', $properties)) {
                $results[$retpath]['displayname'] = rawurlencode(
                    $gallery->data['attribute_name']);
            }
            if (in_array('icon', $properties)) {
                $results[$retpath]['icon'] = $registry->getImageDir()
                    . '/ansel.png';
            }
            if (in_array('browseable', $properties)) {
                $results[$retpath]['browseable'] = $gallery->hasPermission(
                    Horde_Auth::getAuth(), PERMS_READ);
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

    return PEAR::raiseError(_("File not found."), 404);
}

/**
 * Saves an image into the gallery tree.
 *
 * @param string $path          The path where to PUT the file.
 * @param string $content       The file content.
 * @param string $content_type  The file's content type.
 *
 * @return array  The event UIDs, or a PEAR_Error on failure.
 */
function _ansel_put($path, $content, $content_type)
{
    require_once dirname(__FILE__) . '/base.php';

    if (substr($path, 0, 5) == 'ansel') {
        $path = substr($path, 9);
    }
    $path = trim($path, '/');
    $parts = explode('/', $path);

    if (count($parts) < 3) {
        return PEAR::raiseError("Gallery does not exist");
    }
    $image_name = array_pop($parts);
    $gallery_id = end($parts);
    if (!$GLOBALS['ansel_storage']->galleryExists($gallery_id)) {
        return PEAR::raiseError("Gallery does not exist");
    }
    $gallery = $GLOBALS['ansel_storage']->getGallery($gallery_id);
    if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
        return PEAR::raiseError(_("Access denied adding photos to \"%s\"."));
    }

    return $gallery->addImage(array('image_type' => $content_type,
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
function _ansel_commentCallback($image_id)
{
    require_once dirname(__FILE__) . '/base.php';

    if (!$GLOBALS['conf']['comments']['allow']) {
        return false;
    }

    $image = $GLOBALS['ansel_storage']->getImage($image_id);
    if (!$image || is_a($image, 'PEAR_Error')) {
        return false;
    }

    return $image->filename;
}

/**
 * Checks if applications allows comments
 *
 * @return boolean
 */
function _ansel_hasComments()
{
    if (($GLOBALS['conf']['comments']['allow'] == 'all' ||
        ($GLOBALS['conf']['comments']['allow'] == 'authenticated' &&
         Horde_Auth::getAuth()))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Returns decoded image data
 *
 * @param string $data         The id of the image.
 * @param string $encoding     The encoding type for the image data.
 *                             (none, base64, or binhex)
 * @param string $compression  The compression type for the image data.
 *                             (none, gzip, or lzf)
 * @param boolean $upload      Process direction (true of encode/compress or false if decode/decompress)
 *
 * @return string  The image path.
 */
function _getImageData($data, $encoding = 'none', $compression = 'none', $upload = true)
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
 * @param integer $app         Application used if null then use default.
 * @param integer $gallery_id  The gallery id to add the image to.
 * @param array $image         Image data array.  This can either be the return
 *                             from Horde_Form_Type_image:: or an array with
 *                             the following four fields:
 *                             'filename', 'description', 'data', 'type'
 * @param integer $default     Set this image as default in the gallery?
 * @param array $gallery_data  Any gallery parameters to change at this time.
 * @param string $encoding     The encoding type for the image data.
 *                             (none, base64, or binhex)
 * @param string $slug         Use gallery slug instead of id. (Pass '0' or null
 *                             to gallery_id parameter).
 * @param string $compression  The compression type for the image data.
 *                             (none, gzip, or lzf)
 *
 * @return mixed  An array of image/gallery data || PEAR_Error
 */
function _ansel_saveImage($app = null, $gallery_id, $image, $default = false,
                          $gallery_data = null, $encoding = null, $slug = null,
                          $compression = 'none', $skiphook = false)
{
    require_once dirname(__FILE__) . '/base.php';

    $image_data = null;

    /* If no app is given use Ansel's own gallery which is initialized
     * in base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    if (isset($image['filename']) &&
        isset($image['description']) &&
        isset($image['data']) &&
        isset($image['type'])) {
        Horde::logMessage(sprintf("Receiving image %s in _ansel_saveImage() with a raw filesize of %i", $image['filename'], strlen($image['data'])), __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $image_data = array('image_filename' => $image['filename'],
                            'image_caption' => $image['description'],
                            'image_type' => $image['type'],
                            'data' => _getImageData($image['data'], $encoding, $compression, true));
    } else {
        Horde::logMessage(sprintf("Receiving image %s in _ansel_saveImage() with a raw filesize of %i", $image['file'], filesize($image['file'])), __FILE__, __LINE__, PEAR_LOG_DEBUG);
    }

    if (is_null($image_data) && getimagesize($image['file']) === false) {
        return PEAR::raiseError(_("The file you uploaded does not appear to be a valid photo."));
    }
    if (empty($slug) && empty($gallery_id)) {
        return PEAR::raiseError(_("A gallery to add this photo to is required."));
    }
    if (!empty($slug)) {
        $gallery = $GLOBALS['ansel_storage']->getGalleryBySlug($slug);
        if (is_a($gallery, 'PEAR_Error')) {
            return $gallery;
        }
    } elseif ($GLOBALS['ansel_storage']->galleryExists($gallery_id)) {
        $gallery = $GLOBALS['ansel_storage']->getGallery($gallery_id);
        if (is_a($gallery, 'PEAR_Error')) {
            return $gallery;
        }
    }
    if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
        return PEAR::raiseError(sprintf(_("Access denied adding photos to \"%s\"."), $gallery->get('name')));
    }
    if (!empty($gallery_data)) {
        foreach ($gallery_data as $key => $value) {
            $gallery->set($key, $value);
        }
        $gallery->save();
    }

    if (is_null($image_data)) {
        $image_data = array(
            'image_filename' => $image['name'],
            'image_caption' => $image['name'],
            'image_type' => $image['name']['type'],
            'data' => file_get_contents($image['file']),
        );
    }

    if (isset($image['tags']) && is_array($image['tags']) &&
        count($image['tags'])) {
            $image_data['tags'] = $image['tags'];
    }

    $image_id = $gallery->addImage($image_data, $default);
    if (is_a($image_id, 'PEAR_Error')) {
        return $image_id;
    }

    // Call the postupload hook if needed
    if (!empty($GLOBALS['conf']['hooks']['postupload']) && !$skiphook) {
        Horde::callHook('_ansel_hook_postupload', array(array($image_id)), 'ansel');
    }

    return array('image_id'   => (int)$image_id,
                 'gallery_id' => (int)$gallery->id,
                 'gallery_slug' => $gallery->get('slug'),
                 'image_count' => (int)$gallery->countImages());
}

/**
 * Notify Ansel that a group of images has just been uploaded. Used for when
 * the _ansel_hook_postupload hook should be called with a group of recently
 * uploaded images, as opposed to calling it once after each image is saved.
 *
 * @param array $image_ids  An array of image ids.
 */
function _ansel_postBatchUpload($image_ids)
{
    require_once dirname(__FILE__) . '/base.php';
    if (!empty($conf['hooks']['postupload'])) {
        return Horde::callHook('_ansel_hook_postupload', array($image_ids), 'ansel');
    }
}

/**
 * Removes an image from a gallery.
 *
 * @param string $app         Application scope to use, if not the default.
 * @param integer $gallery_id The id of gallery.
 * @param string $image_id    The id of image to remove.
 */
function _ansel_removeImage($app = null, $gallery_id, $image_id)
{
    require_once dirname(__FILE__) . '/base.php';

    /* Check global Ansel permissions */
    if (!($GLOBALS['perms']->getPermissions('ansel'))) {
        return PEAR::raiseError(_("Access denied deleting galleries."));
    }

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    $image = $GLOBALS['ansel_storage']->getImage($image_id);
    if (is_a($image, 'PEAR_Error')) {
        return $image;
    }
    $gallery = $GLOBALS['ansel_storage']->getGallery($image->gallery);
    if (is_a($gallery, 'PEAR_Error') ||
        !$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_DELETE)) {

        return PEAR::raiseError(sprintf(_("Access denied deleting photos from \"%s\"."), $gallery->get('name')));
    }
    return $gallery->removeImage($image);
}

/**
 * Add a new gallery to any arbitrary application's Ansel_Shares.
 *
 * @param string $app            Application scope to use, if not the default.
 * @param array $attributes      The gallery attributes
 *                               (@see Ansel_Storage::createGallery).
 * @param array $perm            An array of permission data if Ansel's defaults
 *                               are not desired. Takes an array like:
 *                               array('guest' => PERMS_SHOW | PERMS_READ,
 *                                     'default' => PERMS_SHOW | PERMS_READ);
 * @param integer $parent        The gallery id of the parent gallery, if any.
 *
 * @return mixed  The gallery id of the new gallery | PEAR_Error
 */
function _ansel_createGallery($app = null, $attributes = array(), $perm = null, $parent = null)
{
    require_once dirname(__FILE__) . '/base.php';

    if (!(Horde_Auth::isAdmin() ||
          (!$GLOBALS['perms']->exists('ansel') && Horde_Auth::getAuth()) ||
          $GLOBALS['perms']->hasPermission('ansel', Horde_Auth::getAuth(), PERMS_EDIT))) {
        return PEAR::raiseError(_("Access denied creating new galleries."));
    }

    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    if (!empty($perm)) {
        // The name is inconsequential; it is only used as a container to
        // represent permissions when passed to the Ansel backend.
        $permobj = new Horde_Permission('');
        $permobj->data = $perm;
    } else {
        $permobj = null;
    }

    $gallery = $GLOBALS['ansel_storage']->createGallery($attributes, $permobj, $parent);
    if (is_a($gallery, 'PEAR_Error')) {
        return $gallery;
    }
    return $gallery->id;
}

/**
 * Removes a gallery and its images.
 *
 * @param string $app          Application scope to use, if not the default.
 * @param integer $gallery_id  The id of gallery.
 *
 * @return mixed boolean true | PEAR_Error
 */
function _ansel_removeGallery($app = null, $gallery_id)
{
    require_once dirname(__FILE__) . '/base.php';

    /* Check global Ansel permissions */
    if (!($GLOBALS['perms']->getPermissions('ansel'))) {
        return PEAR::raiseError(_("Access denied deleting galleries."));
    }

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    $gallery = $GLOBALS['ansel_storage']->getGallery($gallery_id);
    if (is_a($gallery, 'PEAR_Error')) {
        return PEAR::raiseError(sprintf(_("Access denied deleting gallery \"%s\"."),
                                        $gallery->getMessage()));
    } elseif (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_DELETE)) {
        return PEAR::raiseError(sprintf(_("Access denied deleting gallery \"%s\"."),
                                        $gallery->get('name')));
    } else {
        $imageList = $gallery->listImages();
        if ($imageList) {
            foreach ($imageList as $id) {
                $gallery->removeImage($id);
            }
        }
        $result = $GLOBALS['ansel_storage']->removeGallery($gallery);
        if (!is_a($result, 'PEAR_Error')) {
            return true;
        } else {
            return PEAR::raiseError(sprintf(_("There was a problem deleting %s: %s"),
                                            $gallery->get('name'),
                                            $result->getMessage()));
        }
    }
}

/**
 * Returns the number of images in a gallery.
 *
 * @param integer $app          Application used; if null then use default.
 * @param integer $gallery_id   The gallery id.
 * @param string  $slug         The gallery slug.
 *
 * @return integer  The number of images in the gallery.
 */
function _ansel_count($app = null, $gallery_id = null, $slug = '')
{
    require_once dirname(__FILE__) . '/base.php';

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    if (!empty($slug)) {
        $gallery = $GLOBALS['ansel_storage']->getGalleryBySlug($slug);
    } else {
        $gallery = $GLOBALS['ansel_storage']->getGallery($gallery_id);
    }

    if (is_a($gallery, 'PEAR_Error')) {
        return 0;
    } else {
        return (int)$gallery->countImages();
    }
}

/**
 * Returns the default image id of a gallery.
 *
 * @param string $app            Application scope to use, if not the default.
 * @param integer $gallery_id    The gallery id.
 * @param string $style          The named style.
 * @param string $slug           The gallery slug.
 *
 * @return integer  The default image id.
 */
function _ansel_getDefaultImage($app = null, $gallery_id = null,
                                $style = 'ansel_default', $slug = '')
{
    require_once dirname(__FILE__) . '/base.php';

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    if (!empty($slug)) {
        $gallery = $GLOBALS['ansel_storage']->getGalleryBySlug($slug);
    } else {
        $gallery = $GLOBALS['ansel_storage']->getGallery($gallery_id);
    }

    if (is_a($gallery, 'PEAR_Error')) {
        return $gallery;
    } else {
        return $gallery->getDefaultImage($style);
    }
}

/**
 * Returns image URL.
 *
 * @param integer $app       Application used.
 * @param integer $image_id  The id of the image.
 * @param string $view       The view ('screen', 'thumb', 'full', etc) to show.
 * @param boolean $full      Return a path that includes the server name?
 * @param string $style      Use this gallery style
 *
 * @return string  The image path.
 */
function _ansel_getImageUrl($app = null, $image_id, $view = 'screen',
                            $full = false, $style = null)
{
    require_once dirname(__FILE__) . '/base.php';

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    return Ansel::getImageUrl($image_id, $view, $full, $style);
}

/**
 * Returns image file content.
 *
 * @param integer $image_id  The id of the image.
 * @param string $view       The view ('screen', 'thumb', 'full', etc) to show.
 * @param string $style      Force use of this gallery style.
 * @param integer $app       Application used.
 * @param string $encoding     The encoding type for the image data.
 *                             (none, base64, or binhex)
 * @param string $compression  The compression type for the image data.
 *                             (none, gzip, or lzf)
 *
 * @return string  The image path.
 */
function _ansel_getImageContent($image_id, $view = 'screen', $style = null,
                                $app = null, $encoding = null, $compression = 'none')
{
    require_once dirname(__FILE__) . '/base.php';

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    // Get image
    $image = $GLOBALS['ansel_storage']->getImage($image_id);
    if (is_a($image, 'PEAR_Error')) {
        return $image;
    }

    // Get gallery
    $gallery = $GLOBALS['ansel_storage']->getGallery($image->gallery);
    if (is_a($gallery, 'PEAR_Error')) {
        return $gallery;
    }

    // Check age and password
    if (!$gallery->hasPasswd() || !$gallery->isOldEnough()) {
        return PEAR::raiseError(_("Locked galleries are not viewable via the api."));
    }

    if ($view == 'full') {
        // Check permissions for full view
        if (!$gallery->canDownload()) {
            return PEAR::RaiseError(sprintf(_("Access denied downloading photos from \"%s\"."), $gallery->get('name')));
        }

        $data = $GLOBALS['ansel_vfs']->read($image->getVFSPath('full'),
                                            $image->getVFSName('full'));
    } else {
        // Load View
        $result = $image->load($view, $style);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Return image content
        $data = $image->_image->raw();
    }

    if (is_a($data, 'PEAR_Error')) {
        return $data;
    }

    return _getImageData($data, $encoding, $compression, false);
}

/**
 * Returns a list of all galleries.
 *
 * @param string $app         Application scope to use, if not the default.
 * @param integer $perm       The level of permissions to require for a gallery
 *                            to be returned.
 * @param integer $parent     The parent gallery id to start searching from.
 *                            This should be either a gallery id or null.
 * @param boolean $allLevels  Return all levels, or just the direct children of
 *                            $parent?
 * @param integer $from       The gallery to start listing at.
 * @param integer $count      The number of galleries to return.
 * @param array $attributes   Restrict the returned galleries to those matching
 *                            $attributes. An array of attribute names => values
 *
 * @return array  An array of gallery information.
 */
function _ansel_listGalleries($app = null, $perm = PERMS_SHOW,
                              $parent = null,
                              $allLevels = true, $from = 0, $count = 0,
                              $attributes = null, $sort_by = null, $direction = 0)
{
    require_once dirname(__FILE__) . '/base.php';

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }


    $galleries = $GLOBALS['ansel_storage']->listGalleries(
        $perm, $attributes, $parent, $allLevels, $from, $count, $sort_by, $direction);

    if (is_a($galleries, 'PEAR_Error')) {
        return $galleries;
    }

    $return = array();
    foreach ($galleries as $gallery) {
        $return[$gallery->id] = array_merge($gallery->data, array('crumbs' => $gallery->getGalleryCrumbData()));
    }

    return $return;
}

/**
 * Returns an array of gallery information.
 *
 * @param array $ids   An array of gallery ids.
 * @param string $app  Application scope to use, if not the default.
 * @param array $slugs An array of gallery slugs.
 *
 * @return mixed  An array of gallery data arrays | PEAR_Error
 */
function _ansel_getGalleries($ids = array(), $app = null, $slugs = array())
{
    require_once dirname(__FILE__) . '/base.php';

    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    if (count($slugs)) {
        $results = $GLOBALS['ansel_storage']->getGalleriesBySlugs($slugs);
    } else {
        $results = $GLOBALS['ansel_storage']->getGalleries($ids);
    }

    if (is_a($results, 'PEAR_Error')) {
        return $results;
    }

    /* We can't just return the results of the getGalleries call - we need to
       ensure the caller has at least PERMS_READ on the galleries. */
    $galleries = array();
    foreach ($results as $gallery) {
        if ($gallery->hasPermission(Horde_Auth::getAuth(), PERMS_READ)) {
            $galleries[$gallery->id] = array_merge($gallery->data, array('crumbs' => $gallery->getGalleryCrumbData()));
        }
    }

    return $galleries;
}

/**
 * Returns a 'select' menu from the list of galleries created by
 * _ansel_listGalleries().
 *
 *
 * @param integer $app        Application used if null then use default.
 * @param integer $perm       The permissions filter to use.
 * @param string $parent      The parent share to start listing at.
 * @param boolean $allLevels  Return all levels, or just the direct
 * @param integer $from       The gallery to start listing at.
 * @param integer $count      The number of galleries to return.
 * @param boolean $default    The gallery_id of the  gallery that is
 *                            selected by default in the returned option
 *                            list.
 */
function _ansel_selectGalleries($app = null, $perm = PERMS_SHOW,
                                $parent = null,
                                $allLevels = true, $from = 0, $count = 0,
                                $default = null)
{
    require_once dirname(__FILE__) . '/base.php';

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    return Ansel::selectGalleries($default, $perm, null, $parent, $allLevels,
                                  $from, $count);
}

/**
 * Returns a list of all images in a gallery.
 *
 * The return has the URL because in a lot of cases you'll want the url
 * also. Using api call getImageURL results in a lot of overhead when
 * e.g. generating a select list.
 *
 * @param string $app          Application scope to use, if not the default.
 * @param integer $gallery_id  Gallery id to get images from.
 * @param integer $perm        The level of permissions to require for a
 *                             gallery to return it.
 * @param string $view         Viewsize to generate URLs for.
 * @param boolean $full        Return a full URL
 * @param integer $from        Start image.
 * @param integer $count       End image.
 * @param string $style        Use this gallery style.
 * @param string $slug         Gallery slug
 *
 * @return array  Two dimensional array with image names ids (key) and urls.
 */
function _ansel_listImages($app = null, $gallery_id = null, $perm = PERMS_SHOW,
                           $view = 'screen', $full = false, $from = 0,
                           $count = 0, $style = null, $slug = '')
{
    require_once dirname(__FILE__) . '/base.php';

    /* If no app is given use Ansel's own gallery which is initialized in
       base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    /* Determine the default gallery when none is given. The first gallery in
       the list is the default gallery. */
    if (is_null($gallery_id) && empty($slug)) {
        $galleries = $GLOBALS['ansel_storage']->listGalleries($perm);
        if (!count($galleries)) {
            return array();
        }
        $keys = array_keys($galleries);
        $gallery_names = array_keys($galleries[$keys[0]]['galleries']);
        $gallery_id = $gallery_names[0];
    } elseif (!empty($slug)) {
        $gallery = $GLOBALS['ansel_storage']->getGalleryBySlug($slug);
    } else {
        $gallery = $GLOBALS['ansel_storage']->getGallery($gallery_id);
    }
    if (is_a($gallery, 'PEAR_Error')) {
        return $gallery;
    }

    $images = $gallery->listImages();
    if (is_a($images, 'PEAR_Error')) {
        return $images;
    }

    $counter = 0;
    $imagelist = array();
    foreach ($images as $id) {
        $image = $GLOBALS['ansel_storage']->getImage($id);
        if (is_a($image, 'PEAR_Error')) {
            return $image;
        }
        $imagelist[$id]['name'] = $image->filename;
        $imagelist[$id]['caption'] = $image->caption;
        $imagelist[$id]['type'] = $image->type;
        $imagelist[$id]['uploaded'] = $image->uploaded;
        $imagelist[$id]['original_date'] = $image->originalDate;
        $imagelist[$id]['url'] = Ansel::getImageUrl($id, $view, $full, $style);
        if (!is_null($app) && $GLOBALS['conf']['vfs']['src'] != 'direct') {
            $imagelist[$id]['url'] = Horde_Util::addParameter($imagelist[$id]['url'],
                                                        'app', $app);
        }
    }
    return $imagelist;
}

/**
 * Return a list of recently added images
 *
 * @param string $app       Application used if null then use default.
 * @param array $galleries  An array of gallery ids to check.  If left empty,
 *                          will search all galleries with the given
 *                          permissions for the current user.
 * @param integer $perms    PERMS_* constant
 * @param string $view      The type of image view to return.
 * @param boolean $full     Return a full URL if this is true.
 * @param integer  $limit   The maximum number of images to return.
 * @param string $style     Force the use of this gallery style
 * @param string $slugs     An array of gallery slugs
 *
 * @return array  An array of image objects.
 */
function _ansel_getRecentImages($app = null, $galleries = array(),
                                $perms = PERMS_SHOW, $view = 'screen',
                                $full = false, $limit = 10, $style = null,
                                $slugs = array())
{
    require_once dirname(__FILE__) . '/base.php';
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }
    $images = $GLOBALS['ansel_storage']->getRecentImages($galleries, $limit, $slugs);
    $imagelist = array();
    foreach ($images as $image) {
        $id = $image->id;
        $imagelist[$id]['id'] = $id;
        $imagelist[$id]['name'] = $image->filename;
        $imagelist[$id]['url'] = Ansel::getImageUrl($id, $view, $full, $style);
        $imagelist[$id]['caption'] = $image->caption;
        $imagelist[$id]['filename'] = $image->filename;
        $imagelist[$id]['gallery'] = $image->gallery;
        $imagelist[$id]['uploaded'] = $image->uploaded;
        $imagelist[$id]['original_date'] = $image->originalDate;

        if (!is_null($app) && $GLOBALS['conf']['vfs']['src'] != 'direct') {
            $imagelist[$id]['url'] = Horde_Util::addParameter($imagelist[$id]['url'],
                                                        'app', $app);
        }
    }
    return $imagelist;

}

/**
 * Counts the number of galleries.
 *
 * @param string $app         Application scope to use, if not the default.
 * @param integer $perm       The level of permissions to require for a gallery
 *                            to return it.
 * @param mixed $attributes   Restrict the galleries counted to those matching
 *                            $attributes. An array of attribute/value pairs or
 *                            a gallery owner username.
 * @param integer $parent     The parent gallery id to start searching at.
 * @param boolean $allLevels  Return all levels, or just the direct children of
 *                            $parent?
 *
 * @return integer  Returns the number of matching galleries.
 */
function _ansel_countGalleries($app = null, $perm = PERMS_SHOW, $attributes = null,
                               $parent = null, $allLevels = true)
{
    require_once dirname(__FILE__) . '/base.php';

    /* If no app is given use Ansel's own gallery which is initialized
     * in base.php */
    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    return $GLOBALS['ansel_storage']->countGalleries(Horde_Auth::getAuth(), $perm,
                                                     $attributes, $parent,
                                                     $allLevels);
}

/**
 * Retrieve the list of used tag_names, tag_ids and the total number
 * of resources that are linked to that tag.
 *
 * @param array $tags  An optional array of tag_ids. If omitted, all tags
 *                     will be included.
 *
 * @return mixed  An array containing tag_name, and total | PEAR_Error
 */
function _ansel_listTagInfo($tags = null)
{
    require_once dirname(__FILE__) . '/base.php';

    return Ansel_Tags::listTagInfo($tags);
}

/**
 * Searches images/galleries tagged with all requested tags.
 * Returns an application-agnostic array (useful for when doing a tag search
 * across multiple applications) containing the following keys:
 * <pre>
 *  'title'    - The title for this resource.
 *  'desc'     - A terse description of this resource.
 *  'view_url' - The URL to view this resource.
 *  'app'      - The Horde application this resource belongs to.
 * </pre>
 *
 * The 'raw' results array can be returned instead by setting $raw = true.
 *
 * @param array $names           An array of tag_names to search for.
 * @param integer $max           The maximum number of stories to return.
 * @param integer $from          The number of the story to start with.
 * @param string $resource_type  An array of channel_ids to limit the search to.
 * @param string $user           Restrict results to resources owned by $user.
 * @param boolean $raw           Return the raw story data?
 * @param string $app            Application scope to use, if not the default.
 *
 * @return mixed  An array of results | PEAR_Error
 */
function _ansel_searchTags($names, $max = 10, $from = 0,
                           $resource_type = 'all', $user = null, $raw = false,
                           $app = null)
{
    require_once dirname(__FILE__) . '/base.php';

    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    } else {
        $app = 'ansel';
    }

    $results = Ansel_Tags::searchTags($names, $max, $from,  $resource_type,
                                      $user);

    /* Check for error or if we requested the raw data array */
    if (is_a($results, 'PEAR_Error') || $raw) {
        return $results;
    }

    $return = array();
    if (!empty($results['images'])) {
        foreach ($results['images'] as $image_id) {
            $image = $GLOBALS['ansel_storage']->getImage($image_id);
            $desc = $image->caption;
            $title = $image->filename;
            $view_url = Ansel::getUrlFor('view',
                                         array('gallery' => $image->gallery,
                                               'image' => $image_id,
                                               'view' => 'Image'),
                                         true);
            $return[] = array('title' => $image->filename,
                              'desc'=> $image->caption,
                              'view_url' => $view_url,
                              'app' => $app);
        }

    }

    if (!empty($results['galleries'])) {
        foreach ($results['galleries'] as $gallery) {
            $gal = $GLOBALS['ansel_storage']->getGallery($gallery);
            $view_url = Horde_Util::addParameter(Horde::applicationUrl('view.php'), array('gallery' => $gallery,
                                                                                    'view' => 'Gallery'));
            $return[] = array('title' => $gal->get('name'),
                              'desc' => $gal->get('desc'),
                              'view_url' => $view_url,
                              'app' => $app);

        }
    }


    return $return;
}

/**
 * Checks if the gallery exists
 *
 * @param string $app          Application scope to use, if not the default.
 * @param integer $gallery_id  The gallery id
 * @param string $slug         The gallery slug
 *
 * @return boolean
 */
function _ansel_galleryExists($app, $gallery_id = null, $slug = '')
{
    require_once dirname(__FILE__) . '/base.php';

    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
    }

    return $GLOBALS['ansel_storage']->galleryExists($gallery_id, $slug);
}

/**
 * Get a list of all configured styles.
 *
 * @return hash of style definitions.
 */
function _ansel_getGalleryStyles()
{
    require_once dirname(__FILE__) . '/base.php';

    return Ansel::getAvailableStyles();
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
function _ansel_renderView($params = array(), $app = null,
                           $view = 'Gallery')
{
    require_once dirname(__FILE__) . '/base.php';

    if (!is_null($app)) {
        $GLOBALS['ansel_storage'] = new Ansel_Storage($app);
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
