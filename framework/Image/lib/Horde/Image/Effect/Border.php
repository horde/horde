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
    var $_params = array('padding' => 0,
                         'borderColor' => 'black',
                         'fillColor' => 'white',
                         'lineWidth' => 1,
                         'roundWidth' => 0);

    /**
     * Draws the border.
     *
     * This draws the configured border to the provided image. Beware, that
     * every pixel inside the border clipping will be overwritten with the
     * background color.
     */
    function apply()
    {
        $o = $this->_params;

        $d = $this->_image->getDimensions();
        $x = $o['padding'];
        $y = $o['padding'];
        $width = $d['width'] - (2 * $o['padding']);
        $height = $d['height'] - (2 * $o['padding']);

        if ($o['roundWidth'] > 0) {
            $this->_image->roundedRectangle($x, $y, $width, $height, $o['roundWidth'], $o['borderColor'], $o['fillColor']);
        } else {
            $this->_image->rectangle($x, $y, $width, $height, $o['borderColor'], $o['fillColor']);
        }
    }

}
