<?php
/**
 * Image effect easily creating small, center-cropped thumbnails.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Imagick_CenterCrop extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *  <pre>
     *    width    - Target width
     *    height   - Target height
     * </pre>
     *
     * @var array
     */
    protected $_params = array();

    public function apply()
    {
        $this->_params = new Horde_Support_Array($this->_params);
        $this->_image->imagick->cropThumbnailImage($this->_params->width, $this->_params->height);
        $this->_image->clearGeometry();

        return;
    }

}