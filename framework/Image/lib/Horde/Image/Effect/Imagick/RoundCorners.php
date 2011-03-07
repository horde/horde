<?php
/**
 * Image effect for rounding image corners.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Imagick_RoundCorners extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *
     *  radius - Radius of rounded corners.
     *
     * @var array
     */
    protected $_params = array('radius' => 10,
                               'background' => 'none',
                               'border' => 0,
                               'bordercolor' => 'none');

    public function apply()
    {
        if (!method_exists($this->_image->imagick, 'roundCorners')) {
                throw new Horde_Image_Exception('Your version of Imagick is not compiled against a recent enough ImageMagick library (> 6.2.8) to use the RoundCorners effect.');
        }

        $round = $this->_params['radius'];
        $result = $this->_image->imagick->roundCorners($round, $round);

        // Using a border?
        if ($this->_params['bordercolor'] != 'none' &&
            $this->_params['border'] > 0) {

            $size = $this->_image->getDimensions();

            $new = new Imagick();
            $new->newImage($size['width'] + $this->_params['border'],
                           $size['height'] + $this->_params['border'],
                           $this->_params['bordercolor']);
            $new->setImageFormat($this->_image->getType());

            $new->roundCorners($round, $round);
            $new->compositeImage($this->_image->imagick, Imagick::COMPOSITE_OVER, 1, 1);
            $this->_image->imagick->clear();
            $this->_image->imagick->addImage($new);
            $new->destroy();
        }

        // If we have a background other than 'none' we need to
        // compose two images together to make sure we *have* a background. We
        // can't use border because we don't want to extend the image area, just
        // fill in the parts removed by the rounding.
        if ($this->_params['background'] != 'none') {
            $size = $this->_image->getDimensions();
            $new = new Imagick();
            $new->newImage($size['width'],
                           $size['height'],
                           $this->_params['background']);
            $new->setImageFormat($this->_image->getType());
            $new->compositeImage($this->_image->imagick, Imagick::COMPOSITE_OVER, 0, 0);
            $this->_image->imagick->clear();
            $this->_image->imagick->addImage($new);
            $new->destroy();
        }

        // Reset width/height since these might have changed
        $this->_image->clearGeometry();

        return true;
    }

}