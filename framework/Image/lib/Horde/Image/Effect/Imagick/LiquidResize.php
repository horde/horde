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
 * Image effect for applying content aware image resizing.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Imagick_LiquidResize extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *   - width: (integer) The target width.
     *   - height: (integer) The target height.
     *   - delta_x: (integer) How much the seam may move on x axis (A value of
     *              0 causes the seam to be straight).
     *   - rigidity: (integer) Introduces a bias for non-straight seams.
     *               Typically zero
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
            // Only supported if ImageMagick is compiled against lqr library.
            if (method_exists($this->_image->imagick, 'liquidRescaleImage')) {
                $this->_image->imagick->liquidRescaleImage(
                    $this->_params->width, $this->_params->height,
                    $this->_params->delta_x,
                    $this->_params->rigidity
                );
            } else {
                throw new Horde_Image_Exception(
                    'Missing support for lqr in ImageMagick.'
                );
            }
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
    }
}
