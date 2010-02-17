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
     * @param string $view   The view we want.
     * @param string $style  A named gallery style.
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
     * @param string $view   Which view to load.
     * @param string $style  The named gallery style.
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
            $this->_image->loadString('original', $this->_data['full']);
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

        /* We've definitely successfully loaded the image now. */
        $this->_loaded[$viewHash] = true;

        /* Get the VFS info. */
        $vfspath = $this->getVFSPath($view, $style);

        /* Read in the requested view. */
        $data = $GLOBALS['ansel_vfs']->read($vfspath, $this->getVFSName($view));
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage($date, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Ansel_Exception($data);
        }

        $this->_data[$viewHash] = $data;
        $this->_image->loadString($vfspath . '/' . $this->id, $data);
        return true;
    }

    /**
     * Check if an image view exists and returns the vfs name complete with
     * the hash directory name prepended if appropriate.
     *
     * @param integer $id    Image id to check
     * @param string $view   Which view to check for
     * @param string $style  A named gallery style
     *
     * @return mixed  False if image does not exists | string vfs name
     *
     * @static
     */
    public function viewExists($id, $view, $style)
    {
        /* We cannot check empty styles since we cannot get the hash */
        if (empty($style)) {
            return false;
        }

        /* Get the VFS path. */
        $view = Ansel_Gallery::getViewHash($view, $style);

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

        if ($GLOBALS['ansel_vfs']->exists($vfspath, $vfsname)) {
            return $view . '/' . $vfsname;
        } else {
            return false;
        }
    }

    /**
     * Creates and caches the given view.
     *
     * @param string $view  Which view to create.
     * @param string $style  A named gallery style
     *
     * @return boolean
     * @throws Ansel_Exception
     */
    public function createView($view, $style = null)
    {
        // HACK: Need to replace the image object with a JPG typed image if
        //       we are generating a screen image. Need to do the replacement
        //       and do it *here* for BC reasons with Horde_Image...and this
        //       needs to be done FIRST, since the view might already be cached
        //       in the VFS.
        if ($view == 'screen' && $GLOBALS['conf']['image']['type'] != 'jpeg') {
            $this->_image = Ansel::getImageObject(array('type' => 'jpeg'));
            $this->_image->reset();
        }

        /* Get the VFS info. */
        $vfspath = $this->getVFSPath($view, $style);
        if ($GLOBALS['ansel_vfs']->exists($vfspath, $this->getVFSName($view))) {
            return true;
        }
        $data = $GLOBALS['ansel_vfs']->read($this->getVFSPath('full'), $this->getVFSName('full'));
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage($data, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Ansel_Exception($data);
        }
        $this->_image->loadString($this->getVFSPath('full') . '/' . $this->id, $data);
        $styleDef = Ansel::getStyleDefinition($style);
        if ($view == 'prettythumb') {
            $viewType = $styleDef['thumbstyle'];
        } else {
            // Screen, Mini, Thumb
            $viewType = ucfirst($view);
        }

        try {
           $iview = Ansel_ImageView::factory($viewType, array('image' => $this, 'style' => $style));
        } catch (Ansel_Exception $e) {
            // It could be we don't support the requested effect, try
            // ansel_default before giving up.
            if ($view == 'prettythumb') {
                // If we still fail, the exception gets thrown up the chain.
                $iview = Ansel_ImageView::factory('Thumb', array('image' => $this, 'style' => 'ansel_default'));
            }
        }

        /* Create the ImageView */
        $iview->create();

        /* Cache the data from the new imageview */
        $view = $this->getViewHash($view, $style);
        try {
            $this->_data[$view] = $this->_image->raw();
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e);
        }

        /* ...and put it in Horde_Image obejct, then save */
        $this->_image->loadString($vfspath . '/' . $this->id, $this->_data[$view]);
        $this->_loaded[$view] = true;
        $GLOBALS['ansel_vfs']->writeData($vfspath, $this->getVFSName($view), $this->_data[$view], true);

        /* Autowatermark the screen view */
        if ($view == 'screen' &&
            $GLOBALS['prefs']->getValue('watermark_auto') &&
            $GLOBALS['prefs']->getValue('watermark_text') != '') {

            $this->watermark('screen');
            $GLOBALS['ansel_vfs']->writeData($vfspath, $this->getVFSName($view), $this->_image->_data);
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

        $results = $GLOBALS['ansel_vfs']->writeData($this->getVFSPath('full'),
                                                $this->getVFSName('full'),
                                                $this->_data['full'], true);

        if ($results instanceof PEAR_Error) {
            throw new Ansel_Exception($results);
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
        // TODO: Get rid of this, $data should only be valid data.
        if (is_a($data, 'PEAR_Error')) {
            throw new Ansel_Exception($data);
        }

        /* Delete old cached data if we are replacing the full image */
        if ($view == 'full') {
            $this->deleteCache();
        }

        $results = $GLOBALS['ansel_vfs']->writeData($this->getVFSPath($view),
                                                $this->getVFSName($view),
                                                $data, true);

        if ($results instanceof PEAR_Error) {
            throw new Ansel_Exception($results);
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
     * @TODO: Move all SQL queries to Ansel_Storage::
     *
     * @return The image id
     * @throws Ansel_Exception
     */
    public function save()
    {
        /* Existing image, just save and exit */
        if ($this->id) {
            /* Save image details */
            return $GLOBALS['ansel_storage']->saveImage($this);
        }

        /* New image, need to save the image files, exif etc... */
        $GLOBALS['ansel_storage']->saveImage($this);

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
            $result = $this->setTags($this->_tags);
            if (is_a($result, 'PEAR_Error')) {
                // Since we got this far, the image has been added, so
                // just log the tag failure.
                Horde::logMessage($result, __LINE__, __FILE__, PEAR_LOG_ERR);
            }
        }

        /* Save again if EXIF changed any values */
        if (!empty($needUpdate)) {
            $GLOBALS['ansel_storage']->saveImage($this);
        }

        return $this->id;
    }

   /**
    * Replace this image's image data.
    *
    */
    function replace($imageData)
    {
        /* Reset the data array and remove all cached images */
        $this->_data = array();
        $this->reset();

        /* Remove attributes */
        $result = $GLOBALS['ansel_db']->exec('DELETE FROM ansel_image_attributes WHERE image_id = ' . (int)$this->id);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERROR);
            return $result;
        }
        /* Load the new image data */
        $this->_getEXIF();
        $this->updateData($imageData);

        return true;
    }

    /**
     * Adds specified EXIF fields to this image's tags. Called during image
     * upload/creation.
     *
     * @param array $fields  An array of EXIF fields to import as a tag.
     *
     */
    function _exifToTags($fields = array())
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
     * Reads the EXIF data from the image and stores in _exif array() as well
     * also populates any local properties that come from the EXIF data.
     *
     * @return mixed  true if any local properties were modified, false otherwise, PEAR_Error on failure
     */
    function _getEXIF()
    {
        /* Clear the local copy */
        $this->_exif = array();

        /* Get the data */
        $imageFile = $GLOBALS['ansel_vfs']->readFile($this->getVFSPath('full'),
                                                     $this->getVFSName('full'));
        if (is_a($imageFile, 'PEAR_Error')) {
            return $imageFile;
        }
        $exif = Horde_Image_Exif::factory($GLOBALS['conf']['exif']['driver'], !empty($GLOBALS['conf']['exif']['params']) ? $GLOBALS['conf']['exif']['params'] : array());
        
        try {
            $exif_fields = $exif->getData($imageFile);
        } catch (Horde_Image_Exception $e) {
            // Log the error, but it's not the end of the world, so just ignore
            Horde::logMessage($e->getMessage, __FILE__, __LINE__, PEAR_LOG_ERR);
            $exif_fields = array();
            return false;
        }

        /* Flag to determine if we need to resave the image data */
        $needUpdate = false;

        /* Populate any local properties that come from EXIF
         * Save any geo data to a seperate table as well */
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
        $insert = $GLOBALS['ansel_db']->prepare('INSERT INTO ansel_image_attributes (image_id, attr_name, attr_value) VALUES (?, ?, ?)');
        foreach ($exif_fields as $name => $value) {
            $result = $insert->execute(array($this->id, $name, Horde_String::convertCharset($value, Horde_Nls::getCharset(), $GLOBALS['conf']['sql']['charset'])));
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            /* Cache it locally */
            $this->_exif[$name] = Horde_Image_Exif::getHumanReadable($name, $value);
        }
        $insert->free();

        return $needUpdate;
    }

    /**
     * Autorotate based on EXIF orientation field. Updates the data in memory
     * only.
     *
     */
    function _autoRotate()
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
     */
    function reset()
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
    function deleteCache($view = 'all')
    {
        /* Delete cached screen image. */
        if ($view == 'all' || $view == 'screen') {
            $GLOBALS['ansel_vfs']->deleteFile($this->getVFSPath('screen'),
                                              $this->getVFSName('screen'));
        }

        /* Delete cached thumbnail. */
        if ($view == 'all' || $view == 'thumb') {
            $GLOBALS['ansel_vfs']->deleteFile($this->getVFSPath('thumb'),
                                              $this->getVFSName('thumb'));
        }

        /* Delete cached mini image. */
        if ($view == 'all' || $view == 'mini') {
            $GLOBALS['ansel_vfs']->deleteFile($this->getVFSPath('mini'),
                                              $this->getVFSName('mini'));
        }

        if ($view == 'all' || $view == 'prettythumb') {

            /* No need to try to delete a hash we already removed */
            $deleted = array();

            /* Need to generate hashes for each possible style */
            $styles = Horde::loadConfiguration('styles.php', 'styles', 'ansel');
            foreach ($styles as $style) {
                $hash =  md5($style['thumbstyle'] . '.' . $style['background']);
                if (empty($deleted[$hash])) {
                    $GLOBALS['ansel_vfs']->deleteFile($this->getVFSPath($hash),
                                                      $this->getVFSName($hash));
                    $deleted[$hash] = true;
                }
            }
        }
    }

    /**
     * Returns the raw data for the given view.
     *
     * @param string $view  Which view to return.
     */
    function raw($view = 'full')
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
     */
    function downloadHeaders($view = 'full')
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
     * @param string $view   Which view to display.
     * @param string $style  Force use of this gallery style.
     */
    function display($view = 'full', $style = null)
    {
        if ($view == 'full' && !$this->_dirty) {

            // Check full photo permissions
            $gallery = $GLOBALS['ansel_storage']->getGallery($this->gallery);
            if (is_a($gallery, 'PEAR_Error')) {
                return $gallery;
            }
            if (!$gallery->canDownload()) {
                throw Horde_Exception_PermissionDenied(sprintf(_("Access denied downloading photos from \"%s\"."), $gallery->get('name')));
            }

            $data = $GLOBALS['ansel_vfs']->read($this->getVFSPath('full'),
                                                $this->getVFSName('full'));

            if (is_a($data, 'PEAR_Error')) {
                throw new Ansel_Exception($data);
            }
            echo $data;
            return;
        }
        try {
            $this->load($view, $style);
        } catch (Ansel_Exception $e) {
            throw new Ansel_Exception($e);
        }

        $this->_image->display();
    }

    /**
     * Wraps the given view into a file.
     *
     * @param string $view  Which view to wrap up.
     */
    function toFile($view = 'full')
    {
        try {
            $this->load($view);
            return $this->_image->toFile($this->_dirty ? false : $this->_data[$view]);
        } catch (Horde_Exception $e) {
            Horde::logMessage($e->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
        }
    }

    /**
     * Returns the dimensions of the given view.
     *
     * @param string $view  The view (size) to check dimensions for.
     */
    function getDimensions($view = 'full')
    {
        try {
            $this->load($view);
            return $this->_image->getDimensions();
        } catch (Horde_Exception $e) {
            Horde::logMessage($e->getMessage(), __FILE__, __LINE__);
        }
    }

    /**
     * Rotates the image.
     *
     * @param string $view The view (size) to work with.
     * @param integer $angle  What angle to rotate the image by.
     */
    function rotate($view = 'full', $angle)
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->rotate($angle);
    }

    function crop($x1, $y1, $x2, $y2)
    {
        $this->_dirty = true;
        $this->_image->crop($x1, $y1, $x2, $y2);
    }

    /**
     * Resize the current image. This operation takes place immediately.
     *
     * @param integer $width        The new width.
     * @param integer $height       The new height.
     * @param boolean $ratio        Maintain original aspect ratio.
     * @param boolean $keepProfile  Keep the image meta data.
     *
     * @return void
     */
    public function resize($width, $height, $ratio = true, $keepProfile = false)
    {
        $this->_image->resize($width, $height, $ratio, $keepProfile);
    }

    /**
     * Converts the image to grayscale.
     *
     * @param string $view The view (size) to work with.
     */
    function grayscale($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->grayscale();
    }

    /**
     * Watermarks the image.
     *
     * @param string $view The view (size) to work with.
     * @param string $watermark  String to use as the watermark.
     */
    function watermark($view = 'full', $watermark = null, $halign = null,
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
            $identity = Horde_Prefs_Identity::singleton();
            $name = $identity->getValue('fullname');
            if (empty($name)) {
                $name = Horde_Auth::getAuth();
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
        $this->_image->addEffect('TextWatermark', $params);
    }

    /**
     * Flips the image.
     *
     * @param string $view The view (size) to work with.
     */
    function flip($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->flip();
    }

    /**
     * Mirrors the image.
     *
     * @param string $view The view (size) to work with.
     */
    function mirror($view = 'full')
    {
        $this->load($view);
        $this->_dirty = true;
        $this->_image->mirror();
    }

    /**
     * Add an effect to the effect stack
     *
     * @param string $type    The effect to add.
     * @param array  $params  The effect parameters.
     *
     * @return mixed
     */
    function addEffect($type, $params = array())
    {
        return $this->_image->addEffect($type, $params);
    }

    /**
     * Apply any pending effects to the underlaying Horde_Image
     *
     * @return void
     */
    public function applyEffects()
    {
        $this->_image->applyEffects();
    }

    /**
     * Returns this image's tags.
     *
     * @return mixed  An array of tags | PEAR_Error
     * @see Ansel_Tags::readTags()
     */
    function getTags()
    {
        global $ansel_storage;

        if (count($this->_tags)) {
            return $this->_tags;
        }
        $gallery = $ansel_storage->getGallery($this->gallery);
        if (is_a($gallery, 'PEAR_Error')) {
            return $gallery;
        }
        if ($gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ)) {
            $res = Ansel_Tags::readTags($this->id);
            if (!is_a($res, 'PEAR_Error')) {
                $this->_tags = $res;
                return $this->_tags;
            } else {
                return $res;
            }
        } else {
            return PEAR::raiseError(_("Access denied viewing this photo."));
        }
    }

    /**
     * Set/replace this image's tags.
     *
     * @param array $tags  An array of tag names to associate with this image.
     */
    function setTags($tags)
    {
        global $ansel_storage;

        $gallery = $ansel_storage->getGallery(abs($this->gallery));
        if ($gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
            // Clear the local cache.
            $this->_tags = array();
            return Ansel_Tags::writeTags($this->id, $tags);
        } else {
            return PEAR::raiseError(_("Access denied adding tags to this photo."));
        }
    }

    /**
     * Get the Ansel_View_Image_Thumb object
     *
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery object.
     * @param string $style          A named gallery style to use.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param array $params          Any additional parameters the Ansel_Tile
     *                               object may need.
     *
     */
    function getTile($parent = null, $style = null, $mini = false,
                     $params = array())
    {
        if (!is_null($parent) && is_null($style)) {
            $style = $parent->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }

        return Ansel_Tile_Image::getTile($this, $style, $mini, $params);
    }

    /**
     * Get the image type for the requested view.
     */
    function getType($view = 'full')
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
     * @param string $view   The view (thumb, prettythumb etc...)
     * @param string $style  The named style.
     *
     * @return string  A md5 hash suitable for use as a key.
     */
    function getViewHash($view, $style = null)
    {
        global $ansel_storage;

        // These views do not care about style...just return the $view value.
        if ($view == 'screen' || $view == 'thumb' || $view == 'mini' ||
            $view == 'full') {

            return $view;
        }

        if (is_null($style)) {
            $gallery = $ansel_storage->getGallery(abs($this->gallery));
            if (is_a($gallery, 'PEAR_Error')) {
                return $gallery;
            }
            $style = $gallery->getStyle();
        } else {
            $style = Ansel::getStyleDefinition($style);
        }
       $view = md5($style['thumbstyle'] . '.' . $style['background']);

       return $view;
    }

    /**
     * Get the image attributes from the backend.
     *
     * @param Ansel_Image $image  The image to retrieve attributes for.
     *                            attributes for.
     * @param boolean $format     Format the EXIF data. If false, the raw data
     *                            is returned.
     *
     * @return array  The EXIF data.
     * @static
     */
    function getAttributes($format = false)
    {
        $attributes = $GLOBALS['ansel_storage']->getImageAttributes($this->id);
        $fields = Horde_Image_Exif::getFields();
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
                $output[] = '<td><strong>' . $description . '</strong></td><td>' . htmlspecialchars($value, ENT_COMPAT, Horde_Nls::getCharset()) . '</td>';
            }
        }

        return $output;
    }

    /**
     * Indicates if this image represents a multipage image.
     *
     * @return boolean
     */
    public function isMultiPage()
    {
        $this->load();
        return $this->_image->getImagePageCount() > 1;
    }

    public function getPageCount()
    {
        return $this->_image->getImagePageCount();
    }

    /**
     * Reset the iterator to the first image in the set.
     *
     * @return void
     */
    public function rewind()
    {
        $this->load();
        $this->_image->rewind();
    }

    /**
     * Return the current image from the internal iterator.
     *
     * @return Horde_Image_Imagick
     */
    public function current()
    {
        $this->load();
        return $this->_buildImageObject($this->_image->current());
    }

    /**
     * Get the index of the internal iterator.
     *
     * @return integer
     */
    public function key()
    {
        $this->load();
        return $this->_image->key();
    }

    /**
     * Advance the iterator
     *
     * @return Horde_Image_Im
     */
    public function next()
    {
        $this->load();
        if ($next = $this->_image->next()) {
            return $this->_buildImageObject($next);
        }

        return false;
    }

    /**
     * Deterimines if the current iterator item is valid.
     *
     * @return boolean
     */
    public function valid()
    {
        $this->load();
        return $this->_image->valid();
    }

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
