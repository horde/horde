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
class Horde_Image_Effect_Imagick_Border extends Horde_Image_Effect_Border
{
    /**
     * Draws the border.
     *
     * This draws the configured border to the provided image. Beware, that
     * every pixel inside the border clipping will be overwritten with the
     * background color.
     */
    public function apply()
    {
        if ($this->_params['preserve']) {
            Horde_Image_Imagick::frameImage(
                $this->_image->imagick,
                $this->_params['bordercolor'],
                $this->_params['borderwidth'],
                $this->_params['borderwidth']
            );
        } else {
            $this->_image->imagick->borderImage(
                new ImagickPixel($this->_params['bordercolor']),
                $this->_params['borderwidth'],
                $this->_params['borderwidth']
            );
        }
    }
}
