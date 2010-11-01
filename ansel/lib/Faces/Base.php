<?php
/**
 * Face recognition class
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 * @author  Duck <duck@obala.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_Faces_Base
{
    /**
     */
    public function canAutogenerate()
    {
        return false;
    }

    /**
     * Get faces
     *
     * @param string $file Picture filename
     */
    protected function _getFaces($file)
    {
        return array();
    }

    /**
     * Get all the coordinates for faces in an image.
     *
     * @param mixed $image  The Ansel_Image or a path to the image to check.
     *
     * @return mixed  Array of face data
     */
    public function getFaces($image)
    {
        if ($image instanceof Ansel_Image) {
            // First check if screen view exists
            $image->load('screen');

            // Make sure we have an on-disk copy of the file.
            $file = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->readFile($image->getVFSPath('screen'),
                                                    $image->getVFSName('screen'));
        } else {
            $file = $image;
        }
        if (empty($file)) {
              return array();
        }

        // Get faces from driver
        $faces = $this->_getFaces($file);
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
     * @return  Array of faces data
     * @throws Horde_Exception
     */
    public function getImageFacesData($image_id, $full = false)
    {
        $sql = 'SELECT face_id, face_name ';
        if ($full) {
            $sql .= ', gallery_id, face_x1, face_y1, face_x2, face_y2';
        }
        $sql .= ' FROM ansel_faces WHERE image_id = ' . (int)$image_id
            . ' ORDER BY face_id DESC';

       Horde::logMessage('SQL Query by Ansel_Faces::getImageFacesData: ' . $sql, 'DEBUG');
       $result = $GLOBALS['ansel_db']->query($sql);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
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
     * @param integer $gallery  gallery_id to get data for.
     *
     * @return mixed  array of faces data.
     * @throws Horde_Exception
     */
    public function getGalleryFaces($gallery)
    {
        $sql = 'SELECT face_id, image_id, gallery_id, face_name FROM ansel_faces '
            . ' WHERE gallery_id = ' . (int)$gallery . ' ORDER BY face_id DESC';

        $result = $GLOBALS['ansel_db']->query($sql);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
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
     * @return mixed  An array of faces data
     */
    protected function _fetchFaces($info, $from = 0, $count = 0)
    {
        // add gallery permission
        // FIXME: This is a REALLY ugly hack, permissions checking like this
        // should be encapsulated by the shares driver and not parsed from
        // an internally generated query string fragment. Will need to split
        // this out into two seperate operations somehow.
        $share = substr($GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->shares->getShareCriteria(
            $GLOBALS['registry']->getAuth(), Horde_Perms::READ), 5);

        $sql = 'SELECT f.face_id, f.gallery_id, f.image_id, f.face_name FROM ansel_faces f, '
                . str_replace('WHERE', 'WHERE (', $share)
                . ' ) AND f.gallery_id = s.share_id'
                . (isset($info['filter']) ? ' AND ' . $info['filter'] : '')
                . ' ORDER BY ' . (isset($info['order']) ? $info['order'] : ' f.face_id DESC');

        $GLOBALS['ansel_db']->setLimit($count, $from);
        $result = $GLOBALS['ansel_db']->query($sql);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
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
     *
     * @return integer  The count of faces
     * @throws Ansel_Exception
     */
    protected function _countFaces($info)
    {
        // add gallery permission
        // FIXME: Ditto on the REALLY ugly hack comment from above!
        $share = substr($GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->shares->getShareCriteria(
            $GLOBALS['registry']->getAuth(), Horde_Perms::READ), 5);

        $sql = 'SELECT COUNT(*) FROM ansel_faces f, '
                . str_replace('WHERE', 'WHERE (', $share)
                . ' ) AND f.gallery_id = s.share_id'
                . (isset($info['filter']) ? ' AND ' . $info['filter'] : '');

        $result = $GLOBALS['ansel_db']->queryOne($sql);
        if ($result instanceof PEAR_Error) {
            throw new Ansel_Exception($result);
        }

        return $result;
    }

    /**
     * Get all faces
     *
     * @param integer $from Offset
     * @param integer $count Limit
     */
    public function allFaces($from = 0, $count = 0)
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
    public function namedFaces($from = 0, $count = 0)
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
    public function ownerFaces($owner, $from = 0, $count = 0)
    {
        $info = array(
            'filter' => 's.share_owner = ' . $GLOBALS['ansel_db']->quote($owner),
            'order' => 'f.face_id DESC'
        );

        if (!$GLOBALS['registry']->getAuth() || $owner != $GLOBALS['registry']->getAuth()) {
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
    public function searchFaces($name, $from = 0, $count = 0)
    {
        $info = array('filter' => 'f.face_name LIKE ' . $GLOBALS['ansel_db']->quote("%$name%"));
        return $this->_fetchFaces($info, $from, $count);
    }

    /**
     * Get faces owned by owner
     *
     * @param string  $owner User
     */
    public function countOwnerFaces($owner)
    {
        $info = array('filter' => 's.share_owner = ' . $GLOBALS['ansel_db']->quote($owner));
        if (!$GLOBALS['registry']->getAuth() || $owner != $GLOBALS['registry']->getAuth()) {
            $info['filter'] .= ' AND s.gallery_passwd IS NULL';
        }

        return $this->_countFaces($info);
    }

    /**
     * Count all faces
     */
    public function countAllFaces()
    {
        return $this->_countFaces(array());
    }

    /**
     * Get named faces
     */
    public function countNamedFaces()
    {
        $sql = 'SELECT COUNT(*) FROM ansel_faces WHERE face_name IS NOT NULL AND face_name <> \'\'';
        return $GLOBALS['ansel_db']->queryOne($sql);
    }

    /**
     * Seach faces for a name
     *
     * @param string  $name Search string
     */
    public function countSearchFaces($name)
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
    public function viewExists($image_id, $face_id, $create = true)
    {
        $vfspath = Ansel_Faces::getVFSPath($image_id) . 'faces';
        $vfsname = $face_id . Ansel_Faces::getExtension();
        if (!$GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->exists($vfspath, $vfsname)) {
            if (!$create) {
                return false;
            }
            $data = $this->getFaceById($face_id, true);
            $image = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($image_id);

            // Actually create the image.
            $this->createView(
                $face_id,
                $image,
                $data['face_x1'],
                $data['face_y1'],
                $data['face_x2'],
                $data['face_y2']);

            $this->saveSignature($image_id, $face_id);
        }

        return true;
    }

    /**
     * Get a Horde_Image object representing the requested face.
     *
     * @param integer $face_id  The requested face_id
     *
     * @return Horde_Image  The requested Horde_Image object
     * @throws Horde_Exception
     */
    public function getFaceImageObject($face_id)
    {
        $face = $this->getFaceById($face_id, true);

        // Load the image for this face
        if (!$this->viewExists($face['image_id'], $face_id, true)) {
            throw new Horde_Exception(sprintf("Unable to create or locate face_id %u", $face_id));
        }
        $vfspath = Ansel_Faces::getVFSPath($face['image_id']) . 'faces';
        $vfsname = $face_id . Ansel_Faces::getExtension();
        $img = Ansel::getImageObject();
        try {
            $data = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->read($vfspath, $vfsname);
        } catch (VFS_Exception $e) {
            throw new Horde_Exception_Prior($e);
        }
        $img->loadString($data);

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
    public function getFaceUrl($image_id, $face_id, $full = false)
    {
        global $conf;

        // If we won't be using img.php to generate it, make sure the image
        // is generated before returning a url to access it.
        if ($conf['vfs']['src'] != 'php') {
            $this->viewExists($image_id, $face_id, true);
        }

        // If not viewing directly out of the VFS, hand off to img.php
        if ($conf['vfs']['src'] != 'direct') {
            return Horde::url('faces/img.php', $full)->add('face', $face_id);
        } else {
            $path = substr(str_pad($image_id, 2, 0, STR_PAD_LEFT), -2) . '/faces';
            return $GLOBALS['conf']['vfs']['path'] . htmlspecialchars($path . '/' . $face_id . Ansel_Faces::getExtension());
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
     * @throws Ansel_Exception, Horde_Exception_PermissionDenied
     */
    public function saveCustomFace($face_id, $image, $x1, $y1, $x2, $y2, $name = '')
    {
        $image = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($image);
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($image->gallery);
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied('Access denied editing the photo.');
        }

        if (empty($face_id)) {
            $new = true;
            $face_id = $GLOBALS['ansel_db']->nextId('ansel_faces');
            if ($face_id instanceof PEAR_Error) {
                throw new Ansel_Exception($face_id);
            }
        }

        // The user edits the screen image not the full image
        $image->load('screen');

        // Process the image
        $this->createView($face_id,
                          $image,
                          $x1,
                          $y1,
                          $x2,
                          $y2);

        // Clean up as images are static and all gallery images data will remain in memory
        $image->reset();

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
        if ($q instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($q);
        }
        $result = $q->execute($params);
        $q->free();
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
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
     * @throws Horde_Exception_PermissionDenied
     * @throws Ansel_Exception
     */
    public function getFromPicture($image, $create = false)
    {
        // get image if ID is passed
        if (!($image instanceof Ansel_Image)) {
            $image = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getImage($image);
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($image->gallery);
            if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                throw new Horde_Exception_PermissionDenied('Access denied editing the photo.');
            }
        }

        // Get the rectangles for any faces in this image.
        $faces = $this->getFaces($image);
        if (empty($faces)) {
            return array();
        }

        // Clean up any existing faces we may have had in this image.
        Ansel_Faces::delete($image);

        // Process faces
        $fids = array();
        foreach ($faces as $i => $rect) {
            // Create Face id
            $face_id = $GLOBALS['ansel_db']->nextId('ansel_faces');
            if ($face_id instanceof PEAR_Error) {
                throw new Ansel_Exception($face_id);
            }

            // Store face id db
            $sql = 'INSERT INTO ansel_faces (face_id, image_id, gallery_id, face_x1, '
                    . ' face_y1, face_x2, face_y2)'
                    . ' VALUES (?, ?, ?, ?, ?, ?, ?)';

            $params = $this->_getParamsArray($face_id, $image, $rect);

            $q = $GLOBALS['ansel_db']->prepare($sql, null, MDB2_PREPARE_MANIP);
            if ($q instanceof PEAR_Error) {
                throw new Ansel_Exception($q);
            }
            $result = $q->execute($params);
            $q->free();
            if ($result instanceof PEAR_Error) {
                throw new Ansel_Exception($result);
            }
            if ($create) {
                $this->_createView($face_id, $image, $rect);
                // Clear any loaded views to save on memory usage.
                $image->reset();
                $this->saveSignature($image->id, $face_id);
            }
            $fids[$face_id] = '';
        }

        // Update gallery and image counts
        $GLOBALS['ansel_db']->exec('UPDATE ansel_images SET image_faces = ' . count($fids) . ' WHERE image_id = ' . $image->id);
        $GLOBALS['ansel_db']->exec('UPDATE ansel_shares SET attribute_faces = attribute_faces + ' . count($fids) . ' WHERE gallery_id = ' . $image->gallery);

        // Expire gallery cache
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire('Ansel_Gallery' . $gallery->id);
        }

        return $fids;
    }

    /**
     * Create a face image from the given data.
     *
     * @param integer $face_id    Face id to generate
     * @param Ansel_Image $image  Image face belongs to
     * @param integer $x1         The top left corner of the cropped image.
     * @param integer $y1         The top right corner of the cropped image.
     * @param integer $x2         The bottom left corner of the cropped image.
     * @param integer $y2         The bottom right corner of the cropped image.
     *
     * @return integer the face id
     */
    public function createView($face_id, $image, $x1, $y1, $x2, $y2)
    {
        // Make sure screen view is created and loaded
        $image->load('screen');

        // Crop to the face
        try {
            $result = $image->getHordeImage()->crop($x1, $y1, $x2, $y2);
        } catch (Horde_Image_Exception $e) {
            throw new Horde_Exception($e->getMessage());
        }
        // Resize and save
        $ext = Ansel_Faces::getExtension();
        $path = Ansel_Faces::getVFSPath($image->id);
        $image->getHordeImage()->resize(50, 50, false);
        try {
            $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->writeData(
                $path . 'faces',
                $face_id . $ext,
                $image->getHordeImage()->raw(),
                true);
        } catch (VFS_Exception $e) {
            throw new Horde_Exception_Prior($e);
        }

        return $face_id;
    }

    /**
     * Get face signature from an existing face image.
     *
     * @param integer $image_id Image ID face belongs to
     * @param integer $face_id Face ID to check
     *
     * @return boolean
     */
    function saveSignature($image_id, $face_id)
    {
        // can we get it?
        if (empty($GLOBALS['conf']['faces']['search']) ||
            Horde_Util::loadExtension('libpuzzle') === false) {

            return '';
        }

        // Ensure we have an on-disk file to read the signature from.
        $path  = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->readFile(
            Ansel_Faces::getVFSPath($image_id) . '/faces',
            $face_id . Ansel_Faces::getExtension());

        $signature = puzzle_fill_cvec_from_file($path);
        if (empty($signature)) {
            return false;
        }
        // save compressed signature
        $sql = 'UPDATE ansel_faces SET face_signature = ? WHERE face_id = ?';
        $params = array(puzzle_compress_cvec($signature), $face_id);
        $q = $GLOBALS['ansel_db']->prepare($sql, null, MDB2_PREPARE_MANIP);
        if ($q instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($q);
        }
        $result = $q->execute($params);
        $q->free();
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
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
        if ($q instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($q);
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
    public function getSignatureFromFile($filename)
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
    public function getFromGallery($gallery_id, $create = false, $force = false)
    {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Injector_Factory_Storage')->create()->getGallery($gallery_id);
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            throw new Horde_Exception(sprintf("Access denied editing gallery \"%s\".", $gallery->get('name')));
        }

        $images = $gallery->getImages();
        $faces = array();
        foreach ($images as $image) {
            if ($image->facesCount && $force == false) {
                continue;
            }
            $result = $this->getFromPicture($image, $create);
            if (!empty($result)) {
                $faces[$image->id] = $result;
            }
            unset($image);
        }

        return $faces;
    }

    /**
     * Set face name
     *
     * @param integer $face  Face id
     * @param string $name  Face name
     */
    public function setName($face, $name)
    {
        $sql = 'UPDATE ansel_faces SET face_name = ? WHERE face_id = ?';
        $params = array($name, $face);
        $q = $GLOBALS['ansel_db']->prepare($sql, null, MDB2_PREPARE_MANIP);
        if ($q instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($q);
        }

        return $q->execute($params);
    }

    /**
     * Get face data
     *
     * @param integer $face_id  Face id
     * @param boolean $full     Retreive full face data?
     */
    public function getFaceById($face_id, $full = false)
    {
        $sql = 'SELECT image_id, gallery_id, face_name';
        if ($full) {
            $sql .= ', face_x1, face_y1, face_x2, face_y2, face_signature';
        }
        $sql .= ' FROM ansel_faces WHERE face_id = ?';
        $q = $GLOBALS['ansel_db']->prepare($sql);
        if ($q instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($q);
        }

        $result = $q->execute((int)$face_id);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
        } elseif ($result->numRows() == 0) {
           throw new Horde_Exception('Face does not exist');
        }

        $face = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        if ($face instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($face);
        }

        // Always return the face_id
        $face['face_id'] = $face_id;

        if ($full && $GLOBALS['conf']['faces']['search'] && function_exists('puzzle_uncompress_cvec')) {
            $face['face_signature'] = puzzle_uncompress_cvec($face['face_signature']);
        }

        if (empty($face['face_name'])) {
            $face['galleries'][$face['gallery_id']][] = $face['image_id'];
            return $face;
        }

        $sql = 'SELECT gallery_id, image_id FROM ansel_faces WHERE face_name = ' . $GLOBALS['ansel_db']->quote($face['face_name']);
        $result = $GLOBALS['ansel_db']->query($sql);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
        } elseif ($result->numRows() == 0) {
            throw new Horde_Exception('Face does not exist');
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
    public function getSignatureMatches($signature, $face_id = 0, $from = 0, $count = 0)
    {
        $word_len = $GLOBALS['conf']['faces']['search'];
        $str_len = strlen($signature);
        $times = $str_len / $word_len;

        $indexes = array();
        for ($i = 0; $i < $times; $i++) {
            $indexes[] = '(index_position = '
                . $GLOBALS['ansel_db']->quote($i, 'integer')
                . ' AND index_part = '
                . $GLOBALS['ansel_db']->quote(substr($signature, $i * $word_len, $word_len))
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
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
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
                    puzzle_uncompress_cvec($face['face_signature']))
            );
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
    protected function _getSignatureMatches($a, $b)
    {
        return $a['similarity'] > $b['similarity'];
    }

}
