<?php
/**
 * Effect for creating a polaroid looking image.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Imagick_PolaroidImage extends Horde_Image_Effect
{
    /**
     * Valid parameters for the polaroid effect
     *
     * resize_height    -    The height that each individual thumbnail
     *                       should be resized to before composing on the image.
     *
     * background       -    The color of the image background.
     *
     * angle            -    Angle to rotate the image.
     *
     * shadowcolor      -    The color of the image shadow.
     */

    /**
     * @var array
     */
    protected $_params = array('background' => 'none',
                               'angle' => 0,
                               'shadowcolor' => 'black');

    /**
     * Create the effect
     *
     */
    public function apply()
    {
        if (!method_exists($this->_image->imagick, 'polaroidImage') ||
            !method_exists($this->_image->imagick, 'trimImage')) {
                throw new Horde_Image_Exception('Your version of Imagick is not compiled against a recent enough ImageMagick library to use the PolaroidImage effect.');
        }

        // This determines the color of the underlying shadow.
        $this->_image->imagick->setImageBackgroundColor(new ImagickPixel($this->_params['shadowcolor']));
        $this->_image->imagick->polaroidImage(new ImagickDraw(), $this->_params['angle']);


        // We need to create a new image to composite the polaroid over.
        // (yes, even if it's a transparent background evidently)
        $size = $this->_image->getDimensions();
        $imk = new Imagick();
        $imk->newImage($size['width'], $size['height'], $this->_params['background']);
        $imk->setImageFormat($this->_image->getType());
        $result = $imk->compositeImage($this->_image->imagick, Imagick::COMPOSITE_OVER, 0, 0);
        $this->_image->imagick->clear();
        $this->_image->imagick->addImage($imk);
        $imk->destroy();

        return true;
    }

}