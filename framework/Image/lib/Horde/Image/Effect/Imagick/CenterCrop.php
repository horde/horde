<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
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
 * Image effect easily creating small, center-cropped thumbnails.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Imagick_CenterCrop extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *   - width: (integer) Crop width.
     *   - height: (integer Crop height.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Applies the effect.
     */
    public function apply()
    {
        $this->_params = new Horde_Support_Array($this->_params);
        try {
            $this->_image->imagick->cropThumbnailImage(
                $this->_params->width, $this->_params->height
            );
            $this->_image->clearGeometry();
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
    }
}
