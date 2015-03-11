<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
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
 * Requires IM version 6.3.8-3 or greater.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Im_CenterCrop extends Horde_Image_Effect
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
        $ver = $this->_image->getIMVersion();
        if (is_array($ver) && version_compare($ver[0], '6.3.8') < 0) {
            $initialCrop = $this->_params->width * 2;
            $command = "-resize x{$initialCrop} -resize '{$initialCrop}x<' -resize 50% -gravity center -crop {$this->_params->width}x{$this->_params->height}+0+0 +repage";
        } else {
            $command = "-thumbnail {$this->_params->width}x{$this->_params->height}\^ -gravity center -extent {$this->_params->width}x{$this->_params->height}";
        }
        $this->_image->addPostSrcOperation($command);
        $this->_image->clearGeometry();

        return;
    }

}