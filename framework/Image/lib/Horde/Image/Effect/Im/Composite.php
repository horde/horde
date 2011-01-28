<?php
/**
 * Simple composite effect for composing multiple images. This effect assumes
 * that all images being passed in are already the desired size.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Im_Composite extends Horde_Image_Effect
{
    /**
     * Valid parameters for border effects:
     *
     * 'images'  - an array of Horde_Image objects to overlay.
     *
     *  ...and ONE of the following. If both are provided, the behaviour is
     *  undefined.
     *
     * 'gravity'    - the ImageMagick gravity constant describing placement
     *                (IM driver only so far, not imagick)
     *
     * 'x' and 'y'  - coordinates for the overlay placement.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Draw the border.
     *
     * This draws the configured border to the provided image. Beware,
     * that every pixel inside the border clipping will be overwritten
     * with the background color.
     */
    public function apply()
    {
        $ops = $geometry = $gravity = '';
        if (isset($this->_params['gravity'])) {
            $gravity = ' -gravity ' . $this->_params['gravity'];
        }

        if (isset($this->_params['x']) && isset($this->_params['y'])) {
            $geometry = ' -geometry +' . $this->_params['x'] . '+' . $this->_params['y'] . ' ';
        }
        if (isset($this->_params['compose'])) {
            // The -matte ensures that the destination (background) image
            // has an alpha channel - to avoid black holes in the image.
            $compose = ' -compose ' . $this->_params['compose'] . ' -matte';
        }

        foreach($this->_params['images'] as $image) {
            $temp = $image->toFile();
            $this->_image->addFileToClean($temp);
            $ops .= ' ' . $temp . $gravity . $compose . ' -composite';
        }
        $this->_image->addOperation($geometry);
        $this->_image->addPostSrcOperation($ops);

        return true;
    }

}