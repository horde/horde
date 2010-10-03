<?php
/**
 * Class to describe a single Ansel image.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Image Implements Iterator
{
    /**
     * The gallery id of this image's parent gallery
     *
     * @var integer
     */
    public $gallery;

    /**
     * Image Id
     *
     * @var integer
     */
    public $id = null;

    /**
     * The filename for this image
     *
     * @var string
     */
    public $filename = 'Untitled';

    /**
     * Image caption
     *
     * @var string
     */
    public $caption = '';

    /**
     * The image's mime type
     *
     * @var string
     */
    public $type = 'image/jpeg';

    /**
     * Timestamp of uploaded datetime
     *
     * @var integer
     */
    public $uploaded;

    /**
     * Sort count for this image
     *
     * @var integer
     */
    public $sort;

    /**
     * The number of comments for this image, if available.
     *
     * @var integer
     */
    public $commentCount;

    /**
     * Number of faces in this image
     * @var integer
     */
    public $facesCount;

    /**
     * Latitude
     *
     * @var string
     */
    public $lat;

    /**
     * Longitude
     *
     * @var string
     */
    public $lng;

    /**
     * Textual location
     *
     * @var string
     */
    public $location;

    /**
     * Timestamp for when image was geotagged
     *
     * @var integer
     */
    public $geotag_timestamp;

    /**
     * Timestamp of original date.
     *
     * @var integer
     */
    public $originalDate;

    /**
     * Horde_Image object for this image.
     *
     * @var Horde_Image_Base
     */
    protected $_image;

    /**
     * Dirty flag
     *
     * @var boolean
     */
    protected $_dirty;

    /**
     * Flags for loaded views
     *
     * @var array
     */
    protected $_loaded = array();

    /**
     * Binary image data for loaded views
     *
     * @var array
     */
    protected $_data = array();
    /**
     * Holds an array of tags for this image
     *
     * @var array
     */
    protected $_tags = array();

    /**
     * Cache the raw EXIF data locally
     *
     * @var array
     */
    protected $_exif = array();

    /**
     * Const'r
     *
     * @param array $image
     *
     * @return Ansel_Image
     */
    public function __construct($image = array())
    {
        if ($image) {
            $this->filename = $image['image_filename'];

            if  (!empty($image['gallery_id'])) {
                $this->gallery = $image['gallery_id'];
            }
            if (!empty($image['image_caption'])) {
                $this->caption = $image['image_caption'];
            }
            if (isset($image['image_sort'])) {
                $this->sort = $image['image_sort'];
            }
            if (!empty($image['image_id'])) {
                $this->id = $image['image_id'];
            }
            if (!empty($image['data'])) {
                $this->_data['full'] = $image['data'];
            }
            if (!empty($image['image_uploaded_date'])) {
                $this->uploaded = $image['image_uploaded_date'];
            } else {
                $this->uploaded = time();
            }
            if (!empty($image['image_type'])) {
                $this->type = $image['image_type'];
            }
            if (!empty($image['tags'])) {
                $this->_tags = $image['tags'];
            }
            if (!empty($image['image_faces'])) {
                $this->facesCount = $image['image_faces'];
            }

            $this->location = !empty($image['image_location']) ? $image['image_location'] : '';

            // The following may have to be rewritten by EXIF.
            // EXIF requires both an image id and a stream, so we can't
            // get EXIF data before we save the image to the VFS.
            if (!empty($image['image_original_date'])) {
                $this->originalDate = $image['image_original_date'];
            } else {
                $this->originalDate = $this->uploaded;
            }
            $this->lat = !empty($image['image_latitude']) ? $image['image_latitude'] : '';
            $this->lng = !empty($image['image_longitude']) ? $image['image_longitude'] : '';
            $this->geotag_timestamp = !empty($image['image_geotag_date']) ? $image['image_geotag_date'] : '0';
        }

        $this->_image = Ansel::getImageObject();
        $this->_image->reset();
    }

    /**
     * Obtain a reference to the underlying Horde_Image
     *
     * @return Horde_Image_Base
     */
    public function &getHordeImage()
    {
        return $this->_image;
    }

    /**
     * Return the vfs path for this image.
     *
     * @param string $view        The view we want.
     * @param Ansel_Style $style  A named gallery style.
     *
     * @return string  The vfs path for this image.
     */
    public function getVFSPath($view = 'full', $style = null)
    {
        $view = $this->getViewHash($view, $style);
        return '.horde/ansel/'
                . substr(str_pad($this->id, 2, 0, STR_PAD_LEFT), -2)
                . '/' . $view;
    }

    /**
     * Returns the file name of this image as used in the VFS backend.
     *
     * @return string  This image's VFS file name.
     */
    public function getVFSName($view)
    {
        $vfsname = $this->id;

        if ($view == 'full' && $this->type) {
            $type = strpos($this->type, '/') === false ? 'image/' . $this->type : $this->type;
            if ($ext = Horde_Mime_Magic::mimeToExt($type)) {
                $vfsname .= '.' . $ext;
            }
        } elseif (($GLOBALS['conf']['image']['type'] == 'jpeg') || $view == 'screen') {
            $vfsname .= '.jpg';
        } else {
            $vfsname .= '.png';
        }

        return $vfsname;
    }

    /**
     * Loads the given view into memory.
     *
     * @param string $view        Which view to load.
     * @param Ansel_Style $style  The gallery style.
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function load($view = 'full', $style = null)
    {
        // If this is a new image that hasn't been saved yet, we will
        // already have the full data loaded. If we auto-rotate the image
        // then there is no need to save it just to load it again.
        if ($view == 'full' && !empty($this->_data['full'])) {
            $this->_image->loadString($this->_data['full']);
            $this->_loaded['full'] = true;
            return true;
        }
        $viewHash = $this->getViewHash($view, $style);

        /* If we've already loaded the data, just return now. */
        if (!empty($this->_loaded[$viewHash])) {
            return true;
        }
        $this->createView($view, $style);

        /* If createView() had to resize the full image, we've already
         * loaded the data, so return now. */
        if (!empty($this->_loaded[$viewHash])) {
            return;
        }

        /* Get the VFS info. */
        $vfspath = $this->getVFSPath($view, $style);

        /* Read in the requested view. */
        try {
            $data = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->read($vfspath, $this->getVFSName($view));
        } catch (VFS_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Ansel_Exception($e);
        }

        /* We've definitely successfully loaded the image now. */
        $this->_loaded[$viewHash] = true;
        $this->_data[$viewHash] = $data;
        $this->_image->loadString($data);

        return true;
    }

    /**
     * Check if an image view exists and returns the vfs name complete with
     * the hash directory name prepended if appropriate.
     *
     * @param integer $id         Image id to check
     * @param string $view        Which view to check for
     * @param Ansel_Style $style  Style object
     *
     * @return mixed  False if image does not exists | string vfs name
     */
    static public function viewExists($id, $view, $style)
    {
        /* We cannot check empty styles since we cannot get the hash */
        if (empty($style)) {
            return false;
        }

        /* Get the VFS path. */
        $view = Ansel::getViewHash($view, $style);

        /* Can't call the various vfs methods here, since this method needs
        to be called statically */
        $vfspath = '.horde/ansel/' . substr(str_pad($id, 2, 0, STR_PAD_LEFT), -2) . '/' . $view;

        /* Get VFS name */
        $vfsname = $id . '.';
        if ($GLOBALS['conf']['image']['type'] == 'jpeg' || $view == 'screen') {
            $vfsname .= 'jpg';
        } else {
            $vfsname .= 'png';
        }

        if ($GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->exists($vfspath, $vfsname)) {
            return $view . '/' . $vfsname;
        } else {
            return false;
        }
    }

    /**
     * Creates and caches the given view.
     *
     * @param string $view         Which view to create.
     * @param Ansel_Style  $style  A style object
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function createView($view, $style = null)
    {
        /* Force screen images to ALWAYS be jpegs for performance/size */
        if ($view == 'screen' && $GLOBALS['conf']['image']['type'] != 'jpeg') {
            $this->_image->setType('jpeg');
        }

        /* Get the VFS info. */
        $vfspath = $this->getVFSPath($view, $style);
        if ($GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->exists($vfspath, $this->getVFSName($view))) {
            return true;
        }
        try {
            $data = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->read($this->getVFSPath('full'), $this->getVFSName('full'));
        } catch (VFS_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Ansel_Exception($e);
        }

        $vHash = $this->getViewHash($view, $style);
        $this->_image->loadString($data);
        if ($view == 'prettythumb') {
            $viewType = $style->thumbstyle;
        } else {
            // Screen, Mini, Thumb
            $viewType = ucfirst($view);
        }

        try {
            $iview = Ansel_ImageGenerator::factory($viewType, array('image' => $this, 'style' => $style));
        } catch (Ansel_Exception $e) {
            // It could be we don't support the requested effect, try
            // ansel_default before giving up.
            if ($view == 'prettythumb') {
                $iview = Ansel_ImageGenerator::factory('Thumb', array('image' => $this, 'style' => Ansel::getStyleDefinition('ansel_default')));
            } else {
                // If it wasn't a prettythumb, then something else must be wrong
                throw $e;
            }
        }

        /* Create the ImageGenerator */
        $iview->create();

        /* Cache the data from the new ImageGenerator */
        try {
            $this->_data[$vHash] = $this->_image->raw();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }

        /* ...and put it in Horde_Image obejct, then save */
        $this->_image->loadString($this->_data[$vHash]);
        $this->_loaded[$vHash] = true;
        $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->writeData($vfspath, $this->getVFSName($vHash), $this->_data[$vHash], true);

        /* Autowatermark the screen view */
        if ($view == 'screen' &&
            $GLOBALS['prefs']->getValue('watermark_auto') &&
            $GLOBALS['prefs']->getValue('watermark_text') != '') {

            $this->watermark('screen');
            $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->writeData($vfspath, $this->getVFSName($view), $this->_image->_data);
        }

        return true;
    }

    /**
     * Writes the current data to vfs, used when creating a new image
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    protected function _writeData()
    {
        $this->_dirty = false;

        try {
            $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->writeData(
                $this->getVFSPath('full'), $this->getVFSName('full'),  $this->_data['full'], true);
        } catch (VFS_Exception $e) {
            throw new Ansel_Exception($e);
        }

        return true;
    }

    /**
     * Change the image data. Deletes old cache and writes the new
     * data to the VFS. Used when updating an image
     *
     * @param string $data  The new data for this image.
     * @param string $view  If specified, the $data represents only this
     *                      particular view. Cache will not be deleted.
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function updateData($data, $view = 'full')
    {
        /* Delete old cached data if we are replacing the full image */
        if ($view == 'full') {
            $this->deleteCache();
        }

        try {
            $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->writeData(
                $this->getVFSPath($view), $this->getVFSName($view), $data, true);
        } catch (VFS_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Update the image's geotag data. Saves to backend storage as well, so no
     * need to call self::save()
     *
     * @param string $lat       Latitude
     * @param string $lng       Longitude
     * @param string $location  Textual location
     *
     * @return void
     */
    public function geotag($lat, $lng, $location = '')
    {
        $this->lat = $lat;
        $this->lng = $lng;
        $this->location = $location;
        $this->geotag_timestamp = time();
        $this->save();
    }

    /**
     * Save image details to storage.
     *
     * @return integer image id
     * @throws Ansel_Exception
     */
    public function save()
    {
        /* Existing image, just save and exit */
        if ($this->id) {
            /* Save image details */
            return $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->saveImage($this);
        }

        /* New image, need to save the image files */
        $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->saveImage($this);

        /* The EXIF functions require a stream, so we need to save before we read */
        $this->_writeData();

        /* Get the EXIF data if we are not a gallery key image. */
        if ($this->gallery > 0) {
            $needUpdate = $this->_getEXIF();
        }

        /* Create tags from exif data if desired */
        $fields = @unserialize($GLOBALS['prefs']->getValue('exif_tags'));
        if ($fields) {
            $this->_exifToTags($fields);
        }

        /* Save the tags */
        if (count($this->_tags)) {
            try {
                $this->setTags($this->_tags);
            } catch (Exception $e) {
                // Since we got this far, the image has been added, so
                // just log the tag failure.
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Save again if EXIF changed any values */
        if (!empty($needUpdate)) {
            $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->saveImage($this);
        }

        return $this->id;
    }

    /**
     * Replace this image's image data.
     *
     * @param array $imageData  An array of image data, the same keys as Const'r
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function replace($imageData)
    {
        /* Reset the data array and remove all cached images */
        $this->_data = array();
        $this->reset();

        /* Remove attributes */
        $result = $GLOBALS['ansel_db']->exec('DELETE FROM ansel_image_attributes WHERE image_id = ' . (int)$this->id);
        if ($result instanceof PEAR_Error) {
            throw new Ansel_Exception($result);
        }

        /* Load the new image data */
        $this->_getEXIF();
        $this->updateData($imageData);
    }

    /**
     * Adds specified EXIF fields to this image's tags.
     * Called during image upload/creation.
     *
     * @param array $fields  An array of EXIF fields to import as a tag.
     *
     * @return void
     */
    protected function _exifToTags($fields = array())
    {
        $tags = array();
        foreach ($fields as $field) {
            if (!empty($this->_exif[$field])) {
                if (substr($field, 0, 8) == 'DateTime') {
                    $d = new Horde_Date(strtotime($this->_exif[$field]));
                    $tags[] = $d->format("Y-m-d");
                } elseif ($field == 'Keywords') {
                    $tags = array_merge($tags, explode(',', $this->_exif[$field]));
                } else {
                    $tags[] = $this->_exif[$field];
                }
            }
        }

        $this->_tags = array_merge($this->_tags, $tags);
    }

    /**
     * Reads the EXIF data from the image, caches in the object and writes to
     * storage. Also populates any local properties that come from the EXIF
     * data.
     *
     * @return boolean  True if any local properties were modified, False if not.
     * @throws Ansel_Exception
     */
    protected function _getEXIF()
    {
        /* Clear the local copy */
        $this->_exif = array();

        /* Get the data */
        try {
            $imageFile = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->readFile(
                $this->getVFSPath('full'), $this->getVFSName('full'));
        } catch (VFS_Exception $e) {
            throw new Ansel_Exception($e);
        }
        $exif = Horde_Image_Exif::factory($GLOBALS['conf']['exif']['driver'], !empty($GLOBALS['conf']['exif']['params']) ? $GLOBALS['conf']['exif']['params'] : array());

        try {
            $exif_fields = $exif->getData($imageFile);
        } catch (Horde_Image_Exception $e) {
            // Log the error, but it's not the end of the world, so just ignore
            Horde::logMessage($e, 'ERR');
            $exif_fields = array();
            return false;
        }

        /* Flag to determine if we need to resave the image data */
        $needUpdate = false;

        /* Populate any local properties that come from EXIF */
        if (!empty($exif_fields['GPSLatitude'])) {
            $this->lat = $exif_fields['GPSLatitude'];
            $this->lng = $exif_fields['GPSLongitude'];
            $this->geotag_timestamp = time();
            $needUpdate = true;
        }

        if (!empty($exif_fields['DateTimeOriginal'])) {
            $this->originalDate = $exif_fields['DateTimeOriginal'];
            $needUpdate = true;
        }

        /* Overwrite any existing value for caption with exif data */
        $exif_title = $GLOBALS['prefs']->getValue('exif_title');
        if (!empty($exif_fields[$exif_title])) {
            $this->caption = $exif_fields[$exif_title];
            $needUpdate = true;
        }

        /* Attempt to autorotate based on Orientation field */
        $this->_autoRotate();

        /* Save attributes. */
        foreach ($exif_fields as $name => $value) {
            $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->saveImageAttribute($this->id, $name, $value);
            $this->_exif[$name] = Horde_Image_Exif::getHumanReadable($name, $value);
        }

        return $needUpdate;
    }

    /**
     * Autorotate based on EXIF orientation field. Updates the data in memory
     * only.
     *
     * @return void
     */
    protected function _autoRotate()
    {
        if (isset($this->_exif['Orientation']) && $this->_exif['Orientation'] != 1) {
            switch ($this->_exif['Orientation']) {
            case 2:
                $this->mirror();
                break;

            case 3:
                $this->rotate('full', 180);
                break;

            case 4:
                $this->mirror();
                $this->rotate('full', 180);
                break;

            case 5:
                $this->flip();
                $this->rotate('full', 90);
                break;

            case 6:
                $this->rotate('full', 90);
                break;

            case 7:
                $this->mirror();
                $this->rotate('full', 90);
                break;

            case 8:
                $this->rotate('full', 270);
                break;
            }

            if ($this->_dirty) {
                $this->_exif['Orientation'] = 1;
                $this->data['full'] = $this->raw();
                $this->_writeData();
            }
        }
    }

    /**
     * Reset the image, removing all loaded views.
     *
     * @return void
     */
    public function reset()
    {
        $this->_image->reset();
        $this->_loaded = array();
    }

    /**
     * Deletes the specified cache file.
     *
     * If none is specified, deletes all of the cache files.
     *
     * @param string $view  Which cache file to delete.
     */
    public function deleteCache($view = 'all')
    {
        /* Catch exceptions from VFS */
        try {
            /* Delete cached screen image. (We don't care if the file is not found) */
            if ($view == 'all' || $view == 'screen') {
                $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->deleteFile(
                    $this->getVFSPath('screen'), $this->getVFSName('screen'));
            }

            /* Delete cached thumbnail. */
            if ($view == 'all' || $view == 'thumb') {
                $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->deleteFile(
                    $this->getVFSPath('thumb'), $this->getVFSName('thumb'));
            }

            /* Delete cached mini image. */
            if ($view == 'all' || $view == 'mini') {
                $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->deleteFile(
                    $this->getVFSPath('mini'), $this->getVFSName('mini'));
            }

            if ($view == 'all' || $view == 'prettythumb') {
                $styles = Horde::loadConfiguration('styles.php', 'styles', 'ansel');
                $hashes = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getHashes();
                foreach ($hashes as $hash)
                {
                    $GLOBALS['injector']->getInstance('Horde_Vfs')
                        ->getVfs('images')
                        ->deleteFile($this->getVFSPath($hash), $this->getVFSName($hash));
                }
            }
        } catch (VFS_Exception $e) {}
    }

    /**
     * Returns the raw data for the given view.
     *
     * @param string $view  Which view to return.
     *
     * @return string  The raw binary image data
     */
    public function raw($view = 'full')
    {
        if ($this->_dirty) {
            return $this->_image->raw();
        } else {
            $this->load($view);
            return $this->_data[$view];
        }
    }

    /**
     * Sends the correct HTTP headers to the browser to download this image.
     *
     * @param string $view  The view to download.
     *
     * @return void
     */
    public function downloadHeaders($view = 'full')
    {
        global $browser, $conf;

        $filename = $this->filename;
        if ($view != 'full') {
            if ($ext = Horde_Mime_Magic::mimeToExt('image/' . $conf['image']['type'])) {
                $filename .= '.' . $ext;
            }
        }

        $browser->downloadHeaders($filename);
    }

    /**
     * Display the requested view.
     *
     * @param string $view        Which view to display.
     * @param Ansel_Style $style  Force use of this gallery style.
     *
     * @return void
     * @throws Horde_Exception_PermissionDenied, Ansel_Exception
     */
    public function display($view = 'full', $style = null)
    {
        if ($view == 'full' && !$this->_dirty) {
            // Check full photo permissions
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($this->gallery);
            if ($gallery instanceof PEAR_Error) {
                throw new Ansel_Exception($gallery);
            }
            if (!$gallery->canDownload()) {
                throw Horde_Exception_PermissionDenied(sprintf(_("Access denied downloading photos from \"%s\"."), $gallery->get('name')));
            }

            try {
                $data = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->read(
                    $this->getVFSPath('full'), $this->getVFSName('full'));
            } catch (VFS_Exception $e) {
                throw new Ansel_Exception($e);
            }
            echo $data;
        } else {
            $this->load($view, $style);
            $this->_image->display();
        }
    }

    /**
     * Wraps the given view into a file.
     *
     * @param string $view  Which view to wrap up.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function toFile($view = 'full')
    {
        try {
            $this->load($view);
            return $this->_image->toFile($this->_dirty ? false : $this->_data[$view]);
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Returns the dimensions of the given view.
     *
     * @param string $view  The view (full, screen etc..) to get dimensions for
     *
     * @return array  A hash of 'width and 'height' dimensions.
     * @throws Ansel_Exception
     */
    public function getDimensions($view = 'full')
    {
        try {
            $this->load($view);
            return $this->_image->getDimensions();
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'INFO');
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Rotates the image.
     *
     * @param string $view    The view (size) to work with.
     * @param integer $angle  What angle to rotate the image by.
     *
     * @return void
     */
    public function rotate($view = 'full', $angle)
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->rotate($angle);
    }

    /**
     * Crop this image to desired dimensions. Crops the currently loaded
     * view present in the Horde_Image object.
     *
     * @see Horde_Image_Base::crop for explanation of parameters
     *
     * @param integer $x1
     * @param integer $y1
     * @param integer $x2
     * @param integer $y2
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function crop($x1, $y1, $x2, $y2)
    {
        $this->_dirty = true;
        try {
            $this->_image->crop($x1, $y1, $x2, $y2);
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Resize the current image.
     *
     * @param integer $width        The new width.
     * @param integer $height       The new height.
     * @param boolean $ratio        Maintain original aspect ratio.
     * @param boolean $keepProfile  Keep the image meta data.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function resize($width, $height, $ratio = true, $keepProfile = false)
    {
        try {
            $this->_image->resize($width, $height, $ratio, $keepProfile);
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Converts the image to grayscale.
     *
     * @param string $view The view (screen, full, etc...) to work with.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function grayscale($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;
        try {
            $this->_image->grayscale();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Watermarks the image.
     *
     * @param string $view       The view (size) to work with.
     * @param string $watermark  String to use as the watermark.
     * @param string $halign     Horizontal alignment (Left, Right, Center)
     * @param string $valign     Vertical alignment (Top, Center, Bottom)
     * @param string $font       The font to use (not all image drivers will
     *                           support this).
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function watermark($view = 'full', $watermark = null, $halign = null,
            $valign = null, $font = null)
    {
        if (empty($watermark)) {
            $watermark = $GLOBALS['prefs']->getValue('watermark_text');
        }
        if (empty($halign)) {
            $halign = $GLOBALS['prefs']->getValue('watermark_horizontal');
        }
        if (empty($valign)) {
            $valign = $GLOBALS['prefs']->getValue('watermark_vertical');
        }
        if (empty($font)) {
            $font = $GLOBALS['prefs']->getValue('watermark_font');
        }
        if (empty($watermark)) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity();
            $name = $identity->getValue('fullname');
            if (empty($name)) {
                $name = $GLOBALS['registry']->getAuth();
            }
            $watermark = sprintf(_("(c) %s %s"), date('Y'), $name);
        }

        $this->load($view);
        $this->_dirty = true;
        $params = array('text' => $watermark,
                        'halign' => $halign,
                        'valign' => $valign,
                        'fontsize' => $font);
        if (!empty($GLOBALS['conf']['image']['font'])) {
            $params['font'] = $GLOBALS['conf']['image']['font'];
        }

        try {
            $this->_image->addEffect('TextWatermark', $params);
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Flips the image.
     *
     * @param string $view The view to work with.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function flip($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;

        try {
            $this->_image->flip();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Mirrors the image.
     *
     * @param string $view The view (size) to work with.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function mirror($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;
        try {
            $this->_image->mirror();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Add an effect to the effect stack
     *
     * @param string $type    The effect to add.
     * @param array  $params  The effect parameters.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function addEffect($type, $params = array())
    {
        try {
            $this->_image->addEffect($type, $params);
        } catch (Horde_Image_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Apply any pending effects to the underlaying Horde_Image
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function applyEffects()
    {
        try {
            $this->_image->applyEffects();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Returns this image's tags.
     *
     * @see Ansel_Tags::readTags()
     *
     * @return array An array of tags
     * @throws Horde_Exception_PermissionDenied, Ansel_Exception
     */
    public function getTags()
    {
        if (count($this->_tags)) {
            return $this->_tags;
        }
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($this->gallery);
        if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            return $GLOBALS['injector']->getInstance('Ansel_Tagger')->getTags($this->id, 'image');
        } else {
            throw new Horde_Exception_PermissionDenied(_("Access denied viewing this photo."));
        }
    }

    /**
     * Set/replace this image's tags.
     *
     * @param array $tags  An array of tag names to associate with this image.
     *
     * @return void
     * @throws Horde_Exception_PermissionDenied
     */
    public function setTags($tags)
    {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery(abs($this->gallery));
        if ($gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            // Clear the local cache.
            $this->_tags = array();
            $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag((string)$this->id, $tags, $gallery->get('owner'), 'image');
        } else {
            throw new Horde_Exception_PermissionDenied(_("Access denied adding tags to this photo."));
        }
    }

    /**
     * Get the Ansel_View_Image_Thumb object
     *
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery object.
     * @param Ansel_Style   $style   A gallery definition to use.
     * @param boolean       $mini    Force the use of a mini thumbnail?
     * @param array         $params  Any additional parameters the Ansel_Tile
     *                               object may need.
     *
     * @return string  HTML for this image's view tile.
     *
     */
    public function getTile($parent = null, $style = null, $mini = false, $params = array())
    {
        if (!is_null($parent) && is_null($style)) {
            $style = $parent->getStyle();
        }

        return Ansel_Tile_Image::getTile($this, $style, $mini, $params);
    }

    /**
     * Get the image type for the requested view.
     *
     * @return string  The requested view's mime type
     */
    public function getType($view = 'full')
    {
        if ($view == 'full') {
            return $this->type;
        } elseif ($view == 'screen') {
            return 'image/jpg';
        } else {
            return 'image/' . $GLOBALS['conf']['image']['type'];
        }
    }

    /**
     * Return a hash key for the given view and style.
     *
     * @param string $view        The view (thumb, prettythumb etc...)
     * @param Ansel_Style $style  The style.
     *
     * @return string  A md5 hash suitable for use as a key.
     */
    public function getViewHash($view, $style = null)
    {
        // These views do not care about style...just return the $view value.
        if ($view == 'screen' || $view == 'thumb' || $view == 'mini' || $view == 'full') {
            return $view;
        }

        if (is_null($style)) {
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery(abs($this->gallery));
            $style = $gallery->getStyle();
        }
        $view = md5($style->thumbstyle . '.' . $style->background);

        return $view;
    }

    /**
     * Get the image attributes from the backend.
     *
     * @param boolean $format     Format the EXIF data. If false, the raw data
     *                            is returned.
     *
     * @return array  The EXIF data.
     */
    public function getAttributes($format = false)
    {
        $attributes = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getImageAttributes($this->id);
        $exif = Horde_Image_Exif::factory($GLOBALS['conf']['exif']['driver'], !empty($GLOBALS['conf']['exif']['params']) ? $GLOBALS['conf']['exif']['params'] : array());
        $fields = Horde_Image_Exif::getFields($exif);
        $output = array();

        foreach ($fields as $field => $data) {
            if (!isset($attributes[$field])) {
                continue;
            }
            $value = Horde_Image_Exif::getHumanReadable($field, Horde_String::convertCharset($attributes[$field], $GLOBALS['conf']['sql']['charset']));
            if (!$format) {
                $output[$field] = $value;
            } else {
                $description = isset($data['description']) ? $data['description'] : $field;
                $output[] = '<td><strong>' . $description . '</strong></td><td>' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '</td>';
            }
        }

        return $output;
    }

    /**
     * Indicates if this image represents a multipage image.
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function isMultiPage()
    {
        $this->load();
        try {
            return $this->_image->getImagePageCount() > 1;
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Get the number of pages that a multipage image contains.
     *
     * @return integer  The number of pages.
     * @throws Ansel_Exception
     */
    public function getImagePageCount()
    {
        if (empty($this->_loaded['full'])) {
            $this->load();
        }

        try {
            return $this->_image->getImagePageCount();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Reset the iterator to the first image in the set.
     *
     * @return void
     * @throws Ansel_Exception
     */
    public function rewind()
    {
        if (empty($this->_loaded['full'])) {
            $this->load();
        }
        try {
            $this->_image->rewind();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Return the current image from the internal iterator.
     *
     * @return Ansel_Image
     */
    public function current()
    {
        if (empty($this->_loaded['full'])) {
            $this->load();
        }
        try {
            return $this->_buildImageObject($this->_image->current());
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Get the index of the internal iterator.
     *
     * @return integer
     * @throws Ansel_Exception
     */
    public function key()
    {
        if (empty($this->_loaded['full'])) {
            $this->load();
        }
        try {
            return $this->_image->key();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Advance the iterator
     *
     * @return mixed Ansel_Image or false if not valid()
     */
    public function next()
    {
        if (empty($this->_loaded['full'])) {
            $this->load();
        }
        if ($next = $this->_image->next()) {
            return $this->_buildImageObject($next);
        }

        return false;
    }

    /**
     * Deterimines if the current iterator item is valid.
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function valid()
    {
        if (empty($this->_loaded['full'])) {
            $this->load();
        }
        try {
            return $this->_image->valid();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }
    }

    /**
     * Build an Ansel_Image from a given Horde_Image.
     * Used to wrap iterating the Horde_Image
     *
     * @param Horde_Image_Base $image  The Horde_Image
     *
     * @return Ansel_Image
     */
    protected function _buildImageObject(Horde_Image_Base $image)
    {
        $params = array(
                'image_filename' => $this->filename,
                'data' => $image->raw(),
        );
        $newImage = new Ansel_Image($params);

        return $newImage;
    }

}
