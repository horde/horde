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
class Horde_Image_Effect_Im_LiquidResize extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *   - width: (integer) The target width.
     *   - height: (integer) The target height.
     *   - ratio: (boolean) Keep aspect ratio.
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

        $resWidth = $this->_params->width * 2;
        $resHeight = $this->_params->height * 2;

        $this->_image->addOperation("-size {$resWidth}x{$resHeight}");
        if ($this->_params->get('ratio', true)) {
            $this->_image->addPostSrcOperation('-liquid-rescale' . " {$this->_params->width}x{$this->_params->height}");
        } else {
            $this->_image->addPostSrcOperation('-liquid-rescale' . " {$this->_params->width}x{$this->_params->height}!");
        }
        $this->_image->clearGeometry();
    }

}