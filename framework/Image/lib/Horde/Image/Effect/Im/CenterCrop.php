<?php
/**
 * Image effect easily creating small, center-cropped thumbnails.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Im_CenterCrop extends Horde_Image_Effect
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
        $this->_image->addPostSrcOperation("-thumbnail {$this->_params->width}x{$this->_params->height}^ -gravity center -extent {$this->_params->width}x{$this->_params->height}");
        $this->_image->clearGeometry();

        return;
    }

}