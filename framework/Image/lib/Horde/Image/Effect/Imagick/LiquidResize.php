<?php
/**
 * Image effect for applying content aware image resizing.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Imagick_LiquidResize extends Horde_Image_Effect
{
    /**
     *
     * Valid parameters:
     *  <pre>
     *    width       - The target width
     *    height      - the target height
     *    delta_x     - How much the seam may move on x axis (A value of 0
     *                  causes the seam to be straight).
     *   rigidity     - Introduces a bias for non-straight seams. Typically zero
     * </pre>
     *
     * @var array
     */
    protected $_params = array();

    public function apply()
    {
        $this->_params = new Horde_Support_Array($this->_params);
        try {
            // Only supported if ImageMagick is compiled against lqr library.
            if (method_exists($this->_image->imagick, 'liquidRescaleImage')) {
                $this->_image->imagick->liquidRescaleImage(
                    $this->_params->width, $this->_params->height, $this->_params->delta_x, $this->_params->rigidity);
            } else {
                throw new Horde_Image_Exception('Missing support for lqr in ImageMagick.');
            }
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
    }

}