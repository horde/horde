<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */

/**
 * Image border decorator for the Horde_Image package.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Gd_Border extends Horde_Image_Effect_Border
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
        $type = $this->_image->getType();
        $dimension = $this->_image->getDimensions();
        $newWidth = $dimension['width'] + 2;
        $newHeight = $dimension['height'] + 2;
        $im = $this->_image->create($dimension['width'], $dimension['height']);
        $this->_image->call('imagesavealpha', array($im, true));
        $this->_image->call('imagealphablending', array($im, false));
        $this->_image->call(
            'imagecopy',
            array(
                $im, $this->_image->_im,
                0, 0, 0, 0,
                $dimension['width'], $dimension['height']
            )
        );
        $this->_image->resize(
            $dimension['width'] + 2,
            $dimension['height'] + 2,
            false
        );
        $this->_image->call(
            'imagefilledrectangle',
            array(
                $this->_image->_im,
                0, 0, $dimension['width'] + 1, $dimension['height'] + 1,
                $this->_image->call(
                    'imagecolorallocatealpha',
                    array($this->_image->_im, 0, 0, 0, 127)
                )
            )
        );
        $this->_image->rectangle(
            0,
            0,
            $dimension['width'] + 1,
            $dimension['height'] + 1,
            $this->_params['bordercolor']
        );
        $this->_image->call(
            'imagecopy',
            array(
                $this->_image->_im, $im,
                1, 1, 0, 0,
                $dimension['width'], $dimension['height']
            )
        );
    }
}
