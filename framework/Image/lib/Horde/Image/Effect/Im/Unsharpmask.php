<?php
/**
 * Image effect for applying an unsharpmask.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Im_Unsharpmask extends Horde_Image_Effect
{
    /**
     *
     * Valid parameters:
     *
     *  (float)radius - Thickness of the sharpened edge. Should be greater then
     *                  sigma (or 0, and imagick will attempt to auto choose).
     *                  In general, radius should be roughly output dpi / 150.
     *                  So for display purposes a radius of 0.5 is suggested.
     *
     *  (float)amount - Amount of the difference between original and the
     *                  blur image that gets added back to the original. Can be
     *                  thought of as the "strength" of the effect. Too high
     *                  may cause blocking of shadows and highlights. Given
     *                  a decimal value indicating percentage, e.g. 1.2 = 120%
     *
     *  (float)threshold - Determines how large the brightness delta between
     *                     adjacent pixels needs to be to sharpen the edge.
     *                     Larger values == less sharpening. Useful for
     *                     preventing noisy images from being oversharpened.
     *
     *  (integer)channel - Which channel to apply the sharpening to.
     *
     * @var array
     */
    protected $_params = array('radius' => 0.5,
                               'amount' => 1,
                               'threshold' => 0.05);

    public function apply()
    {
        /* Calculate appropriate sigma:
         * Determines how the sharpening is graduated away from
         * the center pixel of the sharpened edge. In general,
         * if radius < 1, then sigma = radius else sigma = sqrt(radius)
         */
        $this->_params['sigma'] = ($this->_params['radius'] < 1) ?
                $this->_params['radius'] : sqrt($this->_params['radius']);

        $this->_image->addPostSrcOperation("-unsharp {$this->_params['radius']}x{$this->_params['sigma']}+{$this->_params['amount']}+{$this->_params['threshold']}");

        return true;
    }

}