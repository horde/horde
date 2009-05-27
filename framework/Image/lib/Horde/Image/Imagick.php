<?php
/**
 * Imagick driver for the Horde_Image API
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Imagick extends Horde_Image
{
    protected $_imagick;

    public function __construct($params, $context = array())
    {
        parent::__construct($params, $context);
        if (Util::loadExtension('imagick')) {
            ini_set('imagick.locale_fix', 1);
            $this->_imagick = new Imagick();
            $this->_width = max(array($this->_width, 1));
            $this->_height = max(array($this->_height, 1));
            if (!empty($params['filename'])) {
                $this->loadFile($params['filename']);
            } elseif(!empty($params['data'])) {
                $this->loadString(md5($params['data']), $params['data']);
            } else {
                $this->_imagick->newImage($this->_width, $this->_height, $this->_background);
                $this->_data = $this->_imagick->getImageBlob();
            }
            $this->_imagick->setImageFormat($this->_type);
        }
    }

    /**
     * Load image data from a string.
     *
     * @TODO: iterator???
     *
     * @param string $id
     * @param string $image_data
     *
     * @return void
     */
    public function loadString($id, $image_data)
    {
        parent::loadString($id, $image_data);
        $this->_imagick->clear();
        $this->_imagick->readImageBlob($image_data);
        $this->_imagick->setFormat($this->_type);
        $this->_imagick->setIteratorIndex(0);
    }

    /**
     * Load the image data from a file.
     *
     * @param string $filename  The full path and filename to the file to load
     *                          the image data from. The filename will also be
     *                          used for the image id.
     *
     * @return mixed
     */
    public function loadFile($filename)
    {
        // parent function loads image data into $this->_data
        // @TODO: Can we clear the _data variable to save memory?
        parent::loadFile($filename);
        $this->loadFile($this->_data);
    }

    /*
     * Return the raw image data.
     *
     * @param boolean $convert  Ignored for imagick driver.
     *
     * @return string  The raw image data.
     */
    public function raw($convert = false)
    {
        return $this->_imagick->getImageBlob();
    }

    public function reset()
    {
        parent::reset();
        $this->_imagick->clear();
    }

    /**
     * Resize current image.
     *
     * @see Horde_Image_im::resize()
     *
     * @return void
     */
    public function resize($width, $height, $ratio = true, $keepProfile = false)
    {
        if ($keepProfile) {
            $this->_imagick->resizeImage($width, $height, $ratio);
        } else {
            $this->_imagick->thumbnailImage($width, $height, $ratio);
        }
        $this->_width = 0;
        $this->_height = 0;
    }

    /**
     * *ALWAYS* use getDimensions() to get image geometry...instance
     * variables only cache geometry until it changes, then they go
     * to zero.
     *
     */
    public function getDimensions()
    {
        if ($this->_height == 0 && $this->_width == 0) {
            try {
                $size = $this->_imagick->getImageGeometry();
            catch (ImagickException $e) {
                //@TODO - Rethrow as Horde_Image_Exception
            }

            $this->_height = $size['height'];
            $this->_width = $size['width'];
        }

        return array('width' => $this->_width,
                     'height' => $this->_height);

    }

}