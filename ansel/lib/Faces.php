<?php
/**
 * Face recognition class
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Faces {

    /**
     * Attempts to return a reference to a concrete Ansel_Faces instance.
     */
    function &singleton()
    {
        static $face;

        if (!isset($face)) {
            $face = Ansel_Faces::factory();
        }

        return $face;
    }

    /**
     * Create instance
     */
    function factory($driver = null, $params = array())
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['faces']['driver'];
        }

        if (empty($params)) {
            $params = $GLOBALS['conf']['faces'];
        }

        $class_name = 'Ansel_Faces';

        // Load system helpers if possible
        if (Ansel_Faces::autogenerate($driver)) {
            require_once ANSEL_BASE . '/lib/Faces/' . basename($driver)  . '.php';
            $class_name .= '_' . $driver;
            if (!class_exists($class_name)) {
                $err = PEAR::raiseError(_("Face driver does not exist."));
                Horde::logMessage($err, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $err;
            }
        }

        $parser = new $class_name($params);

        return $parser;
    }

    /**
     * Tell if the driver can auto generate faces
     *
     * @param string $driver Driver name
     */
    function autogenerate($driver = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['faces']['driver'];
        }

        return $driver == 'opencv' ||
            ($driver == 'facedetect' &&
             version_compare(PHP_VERSION, '5.0.0', '>'));
    }

    /**
     * Get faces
     *
     * @param string $file Picture filename
     * @abstract
     */
    function _getFaces($file)
    {
        return array();
    }

    /**
     * Get all the coordinates for faces in an image.
     *
     * @param mixed $image  The Ansel_Image or a path to the image to check.
     *
     * @return mixed  Array of face data || PEAR_Error
     */
    function getFaces(&$image)
    {
        if (is_a($image, 'Ansel_Image')) {
            // First check if screen view exists
            if (is_a($result = $image->load('screen'), 'PEAR_Error')) {
                return $result;
            }

            // Make sure we have an on-disk copy of the file.
            $file = $GLOBALS['ansel_vfs']->readFile($image->getVFSPath('screen'),
                                                    $image->getVFSName('screen'));
        } elseif (empty($file) || !is_string($image)) {
              return array();
        }

        // Get faces from driver
        $faces = $this->_getFaces($file);
        if (is_a($faces, 'PEAR_Error')) {
            return $faces;
        }
        if (empty($faces)) {
            return array();
        }

        // Remove faces containg faces
        // for example when 2 are together we can have 3 faces
        foreach ($faces as $face) {
            $id = $this->_isInFace($face, $faces);
            if ($id !== false) {
                unset($faces[$id]);
            }
        }

        return $faces;
    }

    /**
     * Get existing faces data from storage for the given image.
     *
     * Used if we need to build the face image at some point after it is
     * detected.
     *
     * @param integer $image_id  The image_id of the Ansel_Image these faces are
     *                           for.
     * @param boolean $full      Get full face data or just face_id and
     *                           face_name.
     *
     * @return mixed  Array of faces data || PEAR_Error
     */
    function getImageFacesData($image_id, $full = false)
    {
        $sql = 'SELECT face_id, face_name ';
        if ($full) {
            $sql .= ', gallery_id, face_x1, face_y1, face_x2, face_y2';
        }
        $sql .= ' FROM ansel_faces WHERE image_id = ' . (int)$image_id
                . ' ORDER BY face_id DESC';

       Horde::logMessage('SQL Query by Ansel_Faces::getImageFacesData: ' . $sql,
                         __FILE__, __LINE__, PEAR_LOG_DEBUG);
       $result = $GLOBALS['ansel_db']->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif ($result->numRows() == 0) {
            return array();
        }

        $faces = array();
        while ($face = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            if ($full) {
                $faces[$face['face_id']] = array(
                    'face_name' => $face['face_name'],
                    'face_id' => $face['face_id'],
                    'gallery_id' => $face['gallery_id'],
                    'face_x1' => $face['face_x1'],
                    'face_y1' => $face['face_y1'],
                    'face_x2' => $face['face_x2'],
                    'face_y2' => $face['face_y2'],
                    'image_id' => $image_id);
            } else {
                $faces[$face['face_id']] = $face['face_name'];
            }
        }

        return $faces;
    }

    /**
     * Get existing faces data for an entire gallery.
     *
     * @param integer $gallery  gallery_id to get data for.\
     *
     * @return mixed  array of faces data || PEAR_Error
     */
    function getGalleryFaces($gallery)
    {
        $sql = 'SELECT face_id, image_id, gallery_id, face_name FROM ansel_faces '
               . ' WHERE gallery_id = ' . (int)$gallery . ' ORDER BY face_id DESC';

        $result = $GLOBALS['ansel_db']->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif ($result->numRows() == 0) {
            return array();
        }

        $faces = array();
        while ($face = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $faces[$face['face_id']] = array('face_name' => $face['face_name'],
                                      'face_id' => $face['face_id'],
                                      'gallery_id' => $face['gallery_id'],
                                      'image_id' => $face['image_id']);
        }

        return $faces;
    }

    /**
     * Fetchs all faces from all galleries the current user has READ access to?
     *
     * @param array $info     Array of select criteria
     * @param integer $from   Offset
     * @param integer $count  Limit
     *
     * @return mixed  An array of faces data || PEAR_Error
     */
    function _fetchFaces($info, $from = 0, $count = 0)
    {
        // add gallery permission
        // FIXME: This is a REALLY ugly hack, permissions checking like this
        // should be encapsulated by the shares driver and not parsed from
        // an internally generated query string fragment. Will need to split
        // this out into two seperate operations somehow.
        $share = substr($GLOBALS['ansel_storage']->shares->_getShareCriteria(
            Horde_Auth::getAuth(), PERMS_READ), 5);

        $sql = 'SELECT f.face_id, f.gallery_id, f.image_id, f.face_name FROM ansel_faces f, '
                . str_replace('WHERE', 'WHERE (', $share)
                . ' ) AND f.gallery_id = s.share_id'
                . (isset($info['filter']) ? ' AND ' . $info['filter'] : '')
                . ' ORDER BY ' . (isset($info['order']) ? $info['order'] : ' f.face_id DESC');

        $GLOBALS['ansel_db']->setLimit($count, $from);
        $result = $GLOBALS['ansel_db']->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif ($result->numRows() == 0) {
            return array();
        }

        $faces = array();
        while ($face = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $faces[$face['face_id']] = array('face_name' => $face['face_name'],
                                            'face_id' => $face['face_id'],
                                            'gallery_id' => $face['gallery_id'],
                                            'image_id' => $face['image_id']);
        }

        return $faces;
    }

    /**
     * Count faces
     *
     * @param array $info Array of select criteria
     */
    function _countFaces($info)
    {
        // add gallery permission
        // FIXME: Ditto on the REALLY ugly hack comment from above!
        $share = substr($GLOBALS['ansel_storage']->shares->_getShareCriteria(
            Horde_Auth::getAuth(), PERMS_READ), 5);

        $sql = 'SELECT COUNT(*) FROM ansel_faces f, '
                . str_replace('WHERE', 'WHERE (', $share)
                . ' ) AND f.gallery_id = s.share_id'
                . (isset($info['filter']) ? ' AND ' . $info['filter'] : '');

        return $GLOBALS['ansel_db']->queryOne($sql);
    }

    /**
     * Get all faces
     *
     * Note: I removed the 'random' parameter since it won't work across
     *       different RDBMS and it's incredibly resource intensive as it
     *       causes the RDBMS to generate a rand() number for each row and THEN
     *       sort the table by those numbers.
     * @param integer $from Offset
     * @param integer $count Limit
     */
    function allFaces($from = 0, $count = 0)
    {
        $info = array('order' => 'f.face_id DESC');
        return $this->_fetchFaces($info, $from, $count);
    }

    /**
     * Get named faces
     *
     * @param integer $from Offset
     * @param integer $count Limit
     */
    function namedFaces($from = 0, $count = 0)
    {
        $info = array('filter' => 'f.face_name IS NOT NULL AND f.face_name <> \'\'');
        return $this->_fetchFaces($info, $from, $count);
    }

    /**
     * Get faces owned by user
     *
     * @param string  $owner User
     * @param integer $from Offset
     * @param integer $count Limit
     */
    function ownerFaces($owner, $from = 0, $count = 0)
    {
        $info = array(
            'filter' => 's.share_owner = ' . $GLOBALS['ansel_db']->quote($owner),
            'order' => 'f.face_id DESC');

        if ($owner != Horde_Auth::getAuth()) {
            $info['filter'] .= ' AND s.gallery_passwd IS NULL';
        }

        return $this->_fetchFaces($info, $from, $count);
    }

    /**
     * Seach faces for a name
     *
     * @param string  $name   Search string
     * @param integer $from   Offset
     * @param integer $count  Limit
     */
    function searchFaces($name, $from = 0, $count = 0)
    {
        $info = array('filter' => 'f.face_name LIKE ' . $GLOBALS['ansel_db']->quote("%$name%"));
        return $this->_fetchFaces($info, $from, $count);
    }

    /**
     * Get faces owned by owner
     *
     * @param string  $owner User
     */
    function countOwnerFaces($owner)
    {
        $info = array('filter' => 's.share_owner = ' . $GLOBALS['ansel_db']->quote($owner));
        if ($owner != Horde_Auth::getAuth()) {
            $info['filter'] .= ' AND s.gallery_passwd IS NULL';
        }

        return $this->_countFaces($info);
    }

    /**
     * Count all faces
     */
    function countAllFaces()
    {
        return $this->_countFaces(array());
    }

    /**
     * Get named faces
     */
    function countNamedFaces()
    {
        $sql = 'SELECT COUNT(*) FROM ansel_faces WHERE face_name IS NOT NULL AND face_name <> \'\'';
        return $GLOBALS['ansel_db']->queryOne($sql);
    }

    /**
     * Seach faces for a name
     *
     * @param string  $name Search string
     */
    function countSearchFaces($name)
    {
        $info = array('filter' => 'f.face_name LIKE ' . $GLOBALS['ansel_db']->quote("%$name%"));
        return $this->_countFaces($info);
    }


    /**
     * Checks to see that a given face image exists in the VFS.
     *
     * If $create is true, the image is created if it does not
     * exist. Otherwise false is returned if the image does not exist. True is
     * returned both if the image already existed OR if it did not exist, but
     * was successfully created.
     *
     * @param integer $image_id  The image_id the face belongs to.
     * @param integer $face_id   The face_id we are checking for.
     * @param boolean $create    Automatically create the image if it is not
     *                           found.
     *
     * @return boolean  True if image exists at end of function call, false
     *                  otherwise.
     */
    function viewExists($image_id, $face_id, $create = true)
    {
        $vfspath = $this->getVFSPath($image_id) . 'faces';
        $vfsname = $face_id . $this->getExtension();
        if (!$GLOBALS['ansel_vfs']->exists($vfspath, $vfsname)) {
            if (!$create) {
                return false;
            }
            $data = $this->getFaceById($face_id, true);
            if (is_a($data, 'PEAR_Error')) {
                return $data;
            }
            $image = &$GLOBALS['ansel_storage']->getImage($image_id);
            if (is_a($image, 'PEAR_Error')) {
                return $image;
            }

            // Actually create the image.
            $result = $this->createView(
                $face_id,
                $image,
                $data['face_x1'],
                $data['face_y1'],
                $data['face_x2'],
                $data['face_y2']);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            $this->saveSignature($image_id, $face_id);
        }
        return true;
    }

    /**
     * Get a Horde_Image object representing the requested face.
     *
     * @param integer $face_id  The requested face_id
     *
     * @return mixed  The requeste Horde_Image object || PEAR_Error
     */
    function getFaceImageObject($face_id)
    {
        $face = $this->getFaceById($face_id, true);
        if (is_a($face, 'PEAR_Error')) {
            Horde::logMessage($face, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $face;
        }

        // Load the image for this face
        if (!$this->viewExists($face['image_id'], $face_id, true)) {
            $err = PEAR::raiseError(sprintf("Unable to create or locate face_id %u", $face_id));
            Horde::logMessage($err, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $err;
        }
        $vfspath = $this->getVFSPath($face['image_id']) . 'faces';
        $vfsname = $face_id . $this->getExtension();
        $img = Ansel::getImageObject();
        $data = $GLOBALS['ansel_vfs']->read($vfspath, $vfsname);
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage($data, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $data;
        }
        $img->loadString($face_id, $data);
        return $img;
    }

    /**
     * Get a URL for a face image suitable for using as the src attribute in an
     * image tag.
     *
     * @param integer $image_id  Image ID to get url for
     * @param integer $face_id   Face ID to get url for
     * @param boolean $full      Should we generate a full URL?
     *
     * @return string  The URL for the face image suitable for use as the src
     *                 attribute in an <img> tag.
     */
    function getFaceUrl($image_id, $face_id, $full = false)
    {
        global $conf;

        // If we won't be using img.php to generate it, make sure the image
        // is generated before returning a url to access it.
        if ($conf['vfs']['src'] != 'php') {
            $this->viewExists($image_id, $face_id, true);
        }

        // If not viewing directly out of the VFS, hand off to img.php
        if ($conf['vfs']['src'] != 'direct') {
            return Horde::applicationUrl(
                Horde_Util::addParameter('faces/img.php', 'face', $face_id), $full);
        } else {
            $path = substr(str_pad($image_id, 2, 0, STR_PAD_LEFT), -2) . '/faces';
            return $GLOBALS['conf']['vfs']['path'] . htmlspecialchars($path . '/' . $face_id . $this->getExtension());
        }
    }

    /**
     * Get image path
     *
     * @param integer $image Image ID to get
     * @static
     */
    function getVFSPath($image)
    {
        return '.horde/ansel/' . substr(str_pad($image, 2, 0, STR_PAD_LEFT), -2) . '/';
    }

    /**
     * Get filename extension
     *
     * @static
     */
    function getExtension()
    {
        if ($GLOBALS['conf']['image']['type'] == 'jpeg') {
            return '.jpg';
        } else {
            return '.png';
        }
    }

    /**
     * Associates a given rectangle with the given image and creates the face
     * image. Used for setting a face range explicitly.
     *
     * @param integer $face_id   Face id to save
     * @param integer $image     Image face belongs to
     * @param integer $x1        The top left corner of the cropped image.
     * @param integer $y1        The top right corner of the cropped image.
     * @param integer $x2        The bottom left corner of the cropped image.
     * @param integer $y2        The bottom right corner of the cropped image.
     * @param string  $name      Face name
     *
     * @return array Faces found
     */
    function saveCustomFace($face_id, $image, $x1, $y1, $x2, $y2, $name = '')
    {
        $image = &$GLOBALS['ansel_storage']->getImage($image);
        if (is_a($image, 'PEAR_Error')) {
            return $image;
        }
        $gallery = $GLOBALS['ansel_storage']->getGallery($image->gallery);
        if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            return PEAR::raiseError(_("Access denied editing the photo."));
        }

        if (empty($face_id)) {
            $new = true;
            $face_id = $GLOBALS['ansel_db']->nextId('ansel_faces');
            if (is_a($face_id, 'PEAR_Error')) {
                return $face_id;
            }
        }

        // The user edits the screen image not the full image
        $image->load('screen');

        // Process the image
        $result = $this->createView($face_id,
                                    $image,
                                    $x1,
                                    $y1,
                                    $x2,
                                    $y2);

        // Clean up as images are static and all gallery images data will remain in memory
        $image->reset();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Store face id db
        if (empty($new)) {
            $sql = 'UPDATE ansel_faces SET face_name = ?, face_x1 = ?, face_y1 = ?, face_x2 = ?, face_y2 = ?'
                    . ' WHERE face_id = ?';
            $params = array($name,
                            $x1,
                            $y1,
                            $x2,
                            $y2,
                            $face_id);
        } else {

            $sql = 'INSERT INTO ansel_faces (face_id, image_id, gallery_id, face_name, '
                    . ' face_x1, face_y1, face_x2, face_y2)'
                    . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $params = array($face_id,
                            $image->id,
                            $image->gallery,
                            $name,
                            $x1,
                            $y1,
                            $x2,
                            $y2);
        }

        $q = $GLOBALS['ansel_db']->prepare($sql, null, MDB2_PREPARE_MANIP);
        if (is_a($q, 'PEAR_Error')) {
            Horde::logMessage($q, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $q;
        }
        $result = $q->execute($params);
        $q->free();
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        // Update gallery and image counts
        $GLOBALS['ansel_db']->exec('UPDATE ansel_images SET image_faces = image_faces + 1 WHERE image_id = ' . $image->id);
        $GLOBALS['ansel_db']->exec('UPDATE ansel_shares SET attribute_faces = attribute_faces + 1 WHERE gallery_id = ' . $image->gallery);

        // Save signature
        $this->saveSignature($image->id, $face_id);

        return $face_id;
    }


    /**
     * Look for and save faces in a picture, and optionally create the face
     * image.
     *
     * @param mixed $image Image Object/ID to check
     * @param boolen $create Create images or store data?
     *
     * @return array Faces found
     */
    function getFromPicture(&$image, $create = false)
    {
        // get image if ID is passed
        if (!is_a($image, 'Ansel_Image')) {
            $image = &$GLOBALS['ansel_storage']->getImage($image);
            if (is_a($image, 'PEAR_Error')) {
                return $image;
            }
            $gallery = $GLOBALS['ansel_storage']->getGallery($image->gallery);
            if (is_a($gallery, 'PEAR_Error')) {
                return $gallery;
            }
            if (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
                return PEAR::raiseError(_("Access denied editing the photo."));
            }
        }

        // Get the rectangles for any faces in this image.
        $faces = $this->getFaces($image);
        if (is_a($faces, 'PEAR_Error')) {
            return $faces;
        } elseif (empty($faces)) {
            return array();
        }

        // Clean up any existing faces we may have had in this image.
        $result = $this->delete($image);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Process faces
        $fids = array();
        foreach ($faces as $i => $rect) {
            // Create Face id
            $face_id = $GLOBALS['ansel_db']->nextId('ansel_faces');
            if (is_a($face_id, 'PEAR_Error')) {
                Horde::logMessage($face_id, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $face_id;
            }

            // Store face id db
            $sql = 'INSERT INTO ansel_faces (face_id, image_id, gallery_id, face_x1, '
                    . ' face_y1, face_x2, face_y2)'
                    . ' VALUES (?, ?, ?, ?, ?, ?, ?)';

            $params = $this->_getParamsArray($face_id, $image, $rect);

            $q = $GLOBALS['ansel_db']->prepare($sql, null, MDB2_PREPARE_MANIP);
            if (is_a($q, 'PEAR_Error')) {
                Horde::logMessage($q, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $q;
            }
            $result = $q->execute($params);
            $q->free();
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }
            if ($create) {
                // Process image
                $result = $this->_createView($face_id, $image, $rect);

                // Clear any loaded views to save on memory usage.
                // TODO: Not sure if this is really necessary or not.
                $image->reset();
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
                $this->saveSignature($image->id, $face_id);
            }
            $fids[$face_id] = '';

        }

        // Update gallery and image counts
        $GLOBALS['ansel_db']->exec('UPDATE ansel_images SET image_faces = ' . count($fids) . ' WHERE image_id = ' . $image->id);
        $GLOBALS['ansel_db']->exec('UPDATE ansel_shares SET attribute_faces = attribute_faces + ' . count($fids) . ' WHERE gallery_id = ' . $image->gallery);

        // Expire gallery cache
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['cache']->expire('Ansel_Gallery' . $gallery->id);
        }

        return $fids;
    }

    /**
     * Create a face image from the given data.
     *
     * @param integer $face_id   Face id to generate
     * @param integer $image     Image face belongs to
     * @param integer $x1        The top left corner of the cropped image.
     * @param integer $y1        The top right corner of the cropped image.
     * @param integer $x2        The bottom left corner of the cropped image.
     * @param integer $y2        The bottom right corner of the cropped image.
     *
     * @return mixed  the face id or PEAR_Error on failure.
     */
    function createView($face_id, &$image, $x1, $y1, $x2, $y2)
    {
        // Make sure screen view is created and loaded
        $image->load('screen');

        // Crop to the face
        $result = $image->_image->crop($x1, $y1, $x2, $y2);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Resize and save
        $ext = $this->getExtension();
        $path = $this->getVFSPath($image->id);
        $image->_image->resize(50, 50, false);
        $result = $GLOBALS['ansel_vfs']->writeData($path . 'faces', $face_id . $ext,
                                                   $image->_image->raw(), true);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $face_id;
    }

    /**
     * Get get face signature from an existing face image.
     *
     * @param integer $image_id Image ID face belongs to
     * @param integer $face_id Face ID to check
     *
     * @return mixed  True || PEAR_Error
     */
    function saveSignature($image_id, $face_id)
    {
        // can we get it?
        if (empty($GLOBALS['conf']['faces']['search']) ||
            Horde_Util::loadExtension('libpuzzle') === false) {

            return '';
        }

        // Ensure we have an on-disk file to read the signature from.
        $path  = $GLOBALS['ansel_vfs']->readFile($this->getVFSPath($image_id) . '/faces',
                                                 $face_id . $this->getExtension());

        $signature = puzzle_fill_cvec_from_file($path);
        if (empty($signature)) {
            return '';
        }
        // save compressed signature
        $sql = 'UPDATE ansel_faces SET face_signature = ? WHERE face_id = ?';
        $params = array(puzzle_compress_cvec($signature), $face_id);
        $q = $GLOBALS['ansel_db']->prepare($sql, null, MDB2_PREPARE_MANIP);
        if (is_a($q, 'PEAR_Error')) {
            Horde::logMessage($q, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $q;
        }
        $result = $q->execute($params);
        $q->free();
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        // create index
        $word_len = $GLOBALS['conf']['faces']['search'];
        $str_len = strlen($signature);
        $times = $str_len / $word_len;
        $data = array();
        for ($i = 0; $i < $times; $i++) {
            $data[] = array($face_id,
                            $i,
                            substr($signature, $i * $word_len, $word_len));
        }

        $GLOBALS['ansel_db']->exec('DELETE FROM ansel_faces_index WHERE face_id = ' . $face_id);
        $q = &$GLOBALS['ansel_db']->prepare('INSERT INTO ansel_faces_index (face_id, index_position, index_part) VALUES (?, ?, ?)');
        if (is_a($q, 'PEAR_Error')) {
            Horde::logMessage($q, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $q;
        }

        $GLOBALS['ansel_db']->loadModule('Extended');
        $GLOBALS['ansel_db']->executeMultiple($q, $data);
        $q->free();

        return true;
    }

    /**
     * Get an image signature from an arbitrary file. Currently used when
     * searching for faces that appear in a user-supplied image.
     *
     * @param integer $filename Image filename to check
     *
     * @return binary vector signature
     */
    function getSignatureFromFile($filename)
    {
        if ($GLOBALS['conf']['faces']['search'] == 0 ||
            Horde_Util::loadExtension('libpuzzle') === false) {

            return '';
        }

        return puzzle_fill_cvec_from_file($filename);
    }

    /**
     * Get faces for all images in a gallery
     *
     * @param integer $gallery_id  The share_id/gallery_id of the gallery to
     *                             check.
     * @param boolen $create       Create faces and signatures or just store coordniates?
     * @param boolen $force Force recreation even if image has faces
     *
     * @return array Faces found
     */
    function getFromGallery($gallery_id, $create = false, $force = false)
    {
        $gallery = $GLOBALS['ansel_storage']->getGallery($gallery_id);
        if (is_a($gallery, 'PEAR_Error')) {
            return $gallery;
        } elseif (!$gallery->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT)) {
            return PEAR::raiseError(sprintf(_("Access denied editing gallery \"%s\"."), $gallery->get('name')));
        }

        $images = $gallery->getImages();
        if (is_a($images, 'PEAR_Error')) {
            return $images;
        }

        $faces = array();
        foreach ($images as $image) {
            if ($image->facesCount && $force == false) {
                continue;
            }
            $result = $this->getFromPicture($image, $create);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            } elseif (!empty($result)) {
                $faces[$image->id] = $result;
            }
            unset($image);
        }

        return $faces;
    }

    /**
     * Delete faces from VFS and DB storage.
     *
     * @param Ansel_Image $image Image object to delete faces for
     * @param integer $face  Face id
     * @static
     */
    function delete(&$image, $face = null)
    {
        if ($image->facesCount == 0) {
            return true;
        }

        $path = Ansel_Faces::getVFSPath($image->id) . '/faces';
        $ext = Ansel_Faces::getExtension();

        if ($face === null) {
            $sql = 'SELECT face_id FROM ansel_faces WHERE image_id = ' . $image->id;
            $face = $GLOBALS['ansel_db']->queryCol($sql);
            if (is_a($face, 'PEAR_Error')) {
                Horde::logMessage($face, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $face;
            }

            foreach ($face as $id) {
                $GLOBALS['ansel_vfs']->deleteFile($path, $id . $ext);
            }

            $GLOBALS['ansel_db']->exec('DELETE FROM ansel_faces WHERE image_id = ' . $image->id);
            $GLOBALS['ansel_db']->exec('UPDATE ansel_images SET image_faces = 0 WHERE image_id = ' . $image->id . ' AND image_faces > 0 ');
            $GLOBALS['ansel_db']->exec('UPDATE ansel_shares SET attribute_faces = attribute_faces - ' . count($face) . ' WHERE gallery_id = ' . $image->gallery . ' AND attribute_faces > 0 ');
        } else {
            $GLOBALS['ansel_vfs']->deleteFile($path, (int)$face . $ext);
            $GLOBALS['ansel_db']->exec('DELETE FROM ansel_faces WHERE face_id = ' . (int)$face);
            $GLOBALS['ansel_db']->exec('UPDATE ansel_images SET image_faces = image_faces - 1 WHERE image_id = ' . $image->id . ' AND image_faces > 0 ');
            $GLOBALS['ansel_db']->exec('UPDATE ansel_shares SET attribute_faces = attribute_faces - 1 WHERE gallery_id = ' . $image->gallery . ' AND attribute_faces > 0 ');
        }

        return true;
    }

    /**
     * Set face name
     *
     * @param integer $face  Face id
     * @param string $name  Face name
     */
    function setName($face, $name)
    {
        $sql = 'UPDATE ansel_faces SET face_name = ? WHERE face_id = ?';
        $params = array($name, $face);

        $q = $GLOBALS['ansel_db']->prepare($sql, null, MDB2_PREPARE_MANIP);
        if (is_a($q, 'PEAR_Error')) {
            Horde::logMessage($q, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $q;
        }

        return $q->execute($params);
    }

    /**
     * Get face link. Points to the image that this face is from.
     *
     * @param array $face  Face data
     *
     * @static
     * @return string  The url for the image this face belongs to.
     */
    function getLink($face)
    {
        return Ansel::getUrlFor('view',
                                array('view' => 'Image',
                                      'gallery' => $face['gallery_id'],
                                      'image' => $face['image_id']));
    }

    /**
     * Get face data
     *
     * @param integer $face_id  Face id
     * @param boolean $full     Retreive full face data?
     */
    function getFaceById($face_id, $full = false)
    {
        $sql = 'SELECT image_id, gallery_id, face_name';
        if ($full) {
            $sql .= ', face_x1, face_y1, face_x2, face_y2, face_signature';
        }
        $sql .= ' FROM ansel_faces WHERE face_id = ?';
        $q = $GLOBALS['ansel_db']->prepare($sql);
        if (is_a($q, 'PEAR_Error')) {
            Horde::logMessage($q, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $q;
        }

        $result = $q->execute((int)$face_id);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif ($result->numRows() == 0) {
            return PEAR::raiseError(_("Face does not exist"));
        }

        $face = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        if (is_a($face, 'PEAR_Error')) {
            Horde::logMessage($face, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $face;
        }

        // Always return the face_id
        $face['face_id'] = $face_id;

        if ($full && $GLOBALS['conf']['faces']['search'] &&
            function_exists('puzzle_uncompress_cvec')) {
            $face['face_signature'] = puzzle_uncompress_cvec($face['face_signature']);
        }

        if (empty($face['face_name'])) {
            $face['galleries'][$face['gallery_id']][] = $face['image_id'];
            return $face;
        }

        $sql = 'SELECT gallery_id, image_id FROM ansel_faces WHERE face_name = ' . $GLOBALS['ansel_db']->quote($face['face_name']);
        $result = $GLOBALS['ansel_db']->query($sql);

        if (is_a($result, 'PEAR_Error')) {
            return $result;
        } elseif ($result->numRows() == 0) {
            return PEAR::RaiseError(_("Face does not exist"));
        }

        while ($gallery = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $face['galleries'][$gallery['gallery_id']][] = $gallery['image_id'];
        }

        return $face;
    }

    /**
     * Get possible matches from sql index
     *
     * @param binary $signature Image signature
     * @param integer $from Offset
     * @param integer $count Limit
     *
     * @return binary vector signature
     */
    function getSignatureMatches($signature, $face_id = 0, $from = 0, $count = 0)
    {
        $word_len = $GLOBALS['conf']['faces']['search'];
        $str_len = strlen($signature);
        $times = $str_len / $word_len;

        $indexes = array();
        for ($i = 0; $i < $times; $i++) {
            $indexes[] = '(index_position = '
                . $GLOBALS['ansel_db']->quote($i, 'integer')
                . ' AND index_part = '
                . $GLOBALS['ansel_db']->quote(
                    substr($signature, $i * $word_len, $word_len))
                . ')';
        }

        $sql = 'SELECT COUNT(*) as face_matches, i.face_id, f.face_name, '
            . 'f.image_id, f.gallery_id, f.face_signature '
            . 'FROM ansel_faces_index i, ansel_faces f '
            . 'WHERE f.face_id = i.face_id';
        if ($face_id) {
            $sql .= ' AND i.face_id <> '
                . $GLOBALS['ansel_db']->quote($face_id, 'integer');
        }
        if ($indexes) {
            $sql .= ' AND (' . implode(' OR ', $indexes) . ')';
        }
        $sql .= ' GROUP BY i.face_id HAVING face_matches > 0 '
            . 'ORDER BY face_matches DESC';
        $GLOBALS['ansel_db']->setLimit($count, $from);

        $result = $GLOBALS['ansel_db']->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif ($result->numRows() == 0) {
            return array();
        }

        $faces = array();
        while ($face = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $faces[$face['face_id']] = array(
                'face_name' => $face['face_name'],
                'face_id' => $face['face_id'],
                'gallery_id' => $face['gallery_id'],
                'image_id' => $face['image_id'],
                'similarity' => puzzle_vector_normalized_distance(
                    $signature,
                    puzzle_uncompress_cvec($face['face_signature'])));
        }
        uasort($faces, array($this, '_getSignatureMatches'));

        return $faces;
    }

    /**
     * Compare faces by similarity.
     *
     * @param array $a
     * @param array $b
     */
    function _getSignatureMatches($a, $b)
    {
        return $a['similarity'] > $b['similarity'];
    }

    /**
     * Output HTML for this face's tile
     * @static
     */
    function getFaceTile($face)
    {
        $faces = Ansel_Faces::singleton();

        if (!is_array($face)) {
            $face = $faces->getFaceById($face, true);
        }

        $face_id = $face['face_id'];
        $claim_url = Horde::applicationUrl('faces/claim.php');
        $search_url = Horde::applicationUrl('faces/search/image_search.php');

        // The HTML to display the face image.
        $imghtml = sprintf("<img src=\"%s\" class=\"bordered-facethumb\" id=\"%s\" alt=\"%s\" />",
             $faces->getFaceUrl($face['image_id'], $face_id),
             'facethumb' . $face_id,
             htmlspecialchars($face['face_name']));

        $img_view_url = Ansel::getUrlFor('view',
            array('gallery' => $face['gallery_id'],
                  'view' => 'Image',
                  'image'=> $face['image_id'],
                  'havesearch' => false));

        // Build the actual html
        $html = '<div id="face' . $face_id . '"><table><tr><td>'
                . ' <a href="' . $img_view_url . '">' . $imghtml . '</a></td><td>';
        if (!empty($face['face_name'])) {
            $html .= Horde::link(Horde_Util::addParameter(Horde::applicationUrl('faces/face.php'), 'face', $face['face_id'], false)) . $face['face_name'] . '</a><br />';
        }

        // Display the face name or a link to claim the face.
        if (empty($face['face_name']) && $GLOBALS['conf']['report_content']['driver']) {
            $html .= ' <a href="' . Horde_Util::addParameter($claim_url, 'face', $face_id)
                . '" title="' . _("Do you know someone in this photo?") . '">'
                . _("Claim") . '</a>';
        }

        // Link for searching for similar faces.
        $html .= ' <a href="' . Horde_Util::addParameter($search_url, 'face_id', $face_id)
            . '">' . _("Find similar") . '</a>';
        $html .= '</div></td></tr></table>';

        return $html;
    }


}
