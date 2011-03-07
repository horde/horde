<?php
/**
 * Image border Effect for the Horde_Image package.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Im_Border extends Horde_Image_Effect
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
        $this->_image->addPostSrcOperation(sprintf(
            "-bordercolor \"%s\" %s -border %s",
            $this->_params['bordercolor'],
            (!empty($this->_params['preserve']) ? '-compose Copy' : ''),
            $this->_params['borderwidth']));

        return true;
    }

}