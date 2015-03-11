<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */

/**
 * Image border decorator for the Horde_Image package.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Border extends Horde_Image_Effect
{
    /**
     * Valid parameters for border effects:
     *   - bordercolor: Border color. Defaults to black.
     *   - borderwidth: Border thickness, defaults to 1 pixel.
     *   - preserve:    Preserves the alpha transparency layer (if present)
     *
     * @var array
     */
    protected $_params = array(
        'bordercolor' => 'black',
        'borderwidth' => 1,
        'preserve' => true
    );

    /**
     * Draws the border.
     *
     * This draws the configured border to the provided image. Beware, that
     * every pixel inside the border clipping will be overwritten with the
     * background color.
     */
    public function apply()
    {
        $dimension = $this->_image->getDimensions();
        $this->_image->rectangle(
            0,
            0,
            $dimension['width'],
            $dimension['height'],
            $this->_params['bordercolor'],
            'none'
        );
    }
}
