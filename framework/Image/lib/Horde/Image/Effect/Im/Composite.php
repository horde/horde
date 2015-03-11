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
class Horde_Image_Effect_Im_Composite extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *   - images: (array) An array of Horde_Image objects to overlay.
     *   - gravity: (string) The gravity describing the placement. One of None,
     *              Center, East, Forget, NorthEast, North, NorthWest,
     *              SouthEast, South, SouthWest, West
     *   - x and y: (integer) Coordinates for the overlay placement.
     *
     * EITHER gravity OR coordinates may be set. If both are provided, the
     * behaviour is undefined.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Applies the effect.
     */
    public function apply()
    {
        $ops = $geometry = $gravity = '';
        if (isset($this->_params['gravity'])) {
            $gravity = ' -gravity ' . $this->_params['gravity'];
        }

        if (isset($this->_params['x']) && isset($this->_params['y'])) {
            $geometry = ' -geometry +' . $this->_params['x']
                . '+' . $this->_params['y'] . ' ';
        }
        if (isset($this->_params['compose'])) {
            // The -matte ensures that the destination (background) image has
            // an alpha channel - to avoid black holes in the image.
            $compose = ' -compose ' . $this->_params['compose'] . ' -matte';
        }

        foreach ($this->_params['images'] as $image) {
            $temp = $image->toFile();
            $this->_image->addFileToClean($temp);
            $ops .= ' ' . $temp . $gravity . $compose . ' -composite';
        }
        $this->_image->addOperation($geometry);
        $this->_image->addPostSrcOperation($ops);

        return true;
    }
}
