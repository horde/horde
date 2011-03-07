<?php
/**
 * Image border Effect for the Horde_Image package.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Imagick_Border extends Horde_Image_Effect
{
    /**
     * Valid parameters for border effects:
     *
     *   bordercolor     - Border color. Defaults to black.
     *   borderwidth     - Border thickness, defaults to 1 pixel.
     *   preserve        - Preserves the alpha transparency layer (if present)
     *
     * @var array
     */
    protected $_params = array('bordercolor' => 'black',
                               'borderwidth' => 1,
                               'preserve' => true);

    /**
     * Draw the border.
     *
     * This draws the configured border to the provided image. Beware,
     * that every pixel inside the border clipping will be overwritten
     * with the background color.
     */
    public function apply()
    {
        if ($this->_params['preserve']) {
            Horde_Image_Imagick::frameImage($this->_image->imagick,
                                             $this->_params['bordercolor'],
                                             $this->_params['borderwidth'],
                                             $this->_params['borderwidth']);
        } else {
            $this->_image->imagick->borderImage(
                new ImagickPixel($this->_params['bordercolor']),
                $this->_params['borderwidth'],
                $this->_params['borderwidth']);
        }

        return true;
    }

}