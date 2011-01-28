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
class Horde_Image_Effect_Imagick_Composite extends Horde_Image_Effect
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
        foreach ($this->_params['images'] as $image) {
            $topimg = new Imagick();
            $topimg->clear();
            $topimg->readImageBlob($image->raw());

            /* Calculate center for composite (gravity center)*/
            $geometry = $this->_image->imagick->getImageGeometry();
            $x = $geometry['width'] / 2;
            $y = $geometry['height'] / 2;

            if (isset($this->_params['x']) && isset($this->_params['y'])) {
                $x = $this->_params['x'];
                $y = $this->_params['y'];
            }
            $this->_image->_imagick->compositeImage($topimg, Imagick::COMPOSITE_OVER, $x, $y);
        }
        return true;
    }

}