<?php
/**
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */

/**
 * Simple composite effect for composing multiple images. This effect assumes
 * that all images being passed in are already the desired size.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Imagick_Composite extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *   - images: (array) An array of Horde_Image objects to overlay.
     *   - x and y: (integer) Coordinates for the overlay placement.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Applies the effect.
     */
    public function apply()
    {
        foreach ($this->_params['images'] as $image) {
            $topimg = new Imagick();
            $topimg->clear();
            $topimg->readImageBlob($image->raw());

            /* Calculate center for composite (gravity center) */
            $geometry = $this->_image->imagick->getImageGeometry();
            $x = $geometry['width'] / 2;
            $y = $geometry['height'] / 2;

            if (isset($this->_params['x']) && isset($this->_params['y'])) {
                $x = $this->_params['x'];
                $y = $this->_params['y'];
            }
            $this->_image->_imagick->compositeImage(
                $topimg,
                Imagick::COMPOSITE_OVER,
                $x, $y
            );
        }
        return true;
    }
}
