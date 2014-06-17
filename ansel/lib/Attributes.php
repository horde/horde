<?php
/**
 * Copyright 2001-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @copyright 2001-2014 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/gpl GPL
 * @package Ansel
 */
/**
 * Class to wrap exif and attribute functionality.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @copyright 2001-2014 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/gpl GPL
 * @package Ansel
 */
class Ansel_Attributes
{

    /**
     * The exif driver.
     *
     * @var Horde_Image_Exif_Base
     */
    protected $_exif;

    /**
     * Local cache of image's extracted exif data.
     *
     * @var boolean|array  False until populated.
     */
    protected $_imageExif = false;

    /**
     * Image id for bound image.
     *
     * @var integer
     */
    protected $_id;

    /**
     * Const'r
     *
     * @param integer $id  Bound image id.
     */
    public function __construct($id)
    {
        $this->_id = $id;
    }

    /**
     * Extract the image's exif data.
     *
     * @param string $imageFile  Path to a local copy of the image file.
     *
     * @return array  The exif data.
     */
    public function getImageExifData($imageFile)
    {
        if ($this->_imageExif === false) {
            try {
                $this->_imageExif = $this->_getExifDriver()->getData($imageFile);
            } catch (Horde_Image_Exception $e) {
                throw new Ansel_Exception($e->getMessage());
            }
        }

        return $this->_imageExif;
    }

    /**
     * Generates, stores, and returns human readable metadata from the image
     * exif data.
     *
     * @param array  An array of exif data, as returned from self::getImageExifData()
     *
     * @return array  A hash of exif field names and human readable values.
     * @throws Ansel_Exception
     */
    public function imageAttributes($attributes = array())
    {
        if ($this->_imageExif === false && empty($attributes)) {
            throw new Ansel_Exception('Ansel_Attributes::getImageExifData must be called before imageAttributes');
        }
        $exif = array();
        foreach ($this->_imageExif as $name => $value) {
            if (!empty($value)) {
                $GLOBALS['storage']->saveImageAttribute($this->_id, $name, $value);
                $exif[$name] = Horde_Image_Exif::getHumanReadable($name, $value);
            }
        }

        return $exif;
    }

    /**
     * Return a suitable image title from exif data. Taken from the first
     * non-empty Horde_Image_Exif::getTitleFields() field.
     *
     * @return string|boolean  The image title. False if no suitable value
     *                         found.
     */
    public function getTitle()
    {
        foreach (array_keys(Horde_Image_Exif::getTitleFields()) as $field) {
            if (!empty($this->_imageExif[$field])) {
                return $this->_imageExif[$field];
            }
        }

        return false;
    }

    /**
     * Return a suitable image caption from exif data. Taken from the first
     * non-empty Horde_Image_Exif::getDescriptionFields() field.
     *
     * @return string|boolean  The image caption. False if no suitable value
     *                         found.
     */
    public function getCaption()
    {
        foreach (array_keys(Horde_Image_Exif::getDescriptionFields()) as $field) {
            if (!empty($this->_imageExif[$field])) {
                return $this->_imageExif[$field];
            }
        }

        return false;
    }

    /**
     * Helper factory method for a exif driver.
     *
     * @return Horde_Image_Exif_Base
     */
    protected function _getExifDriver()
    {
        global $conf, $injector;

        if (empty($this->_exif)) {
            $params = !empty($conf['exif']['params']) ?
                    $conf['exif']['params'] :
                    array();
            $params['logger'] = $injector->getInstance('Horde_Log_Logger');
            $this->_exif = Horde_Image_Exif::factory(
                $GLOBALS['conf']['exif']['driver'],
                $params
            );
        }

        return $this->_exif;
    }

}