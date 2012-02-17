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
class Horde_Image_Effect_Im_LiquidResize extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *  <pre>
     *    width       - The target width
     *    height      - the target height
     *    ratio       - Keep aspect ratio
     * </pre>
     *
     * @var array
     */
    protected $_params = array();

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