<?php
/**
 * Copyright 2007-2015 Horde LLC (http://www.horde.org/)
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
 * Image effect for adding a drop shadow.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2007-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Imagick_DropShadow extends Horde_Image_Effect
{
    /**
     * Valid parameters: Most are currently ignored for the im version
     * of this effect.
     *
     * @TODO
     *
     * @var array
     */
    protected $_params = array(
        'distance' => 5, // This is used as the x and y offset
        'width' => 2,
        'hexcolor' => '000000',
        'angle' => 215,
        'fade' => 3, // Sigma value
        'padding' => 0,
        'background' => 'none'
    );

    /**
     * Applies the effect.
     */
    public function apply()
    {
        // There is what *I* call a bug in the magickwand interface of Im that
        // Imagick is compiled against. The X and Y parameters are ignored, and
        // the distance of the shadow is determined *solely* by the sigma value
        // which makes it pretty much impossible to have Imagick shadows look
        // identical to Im shadows...
        $shadow = $this->_image->imagick->clone();
        $shadow->setImageBackgroundColor(new ImagickPixel('black'));
        $shadow->shadowImage(
            80,
            $this->_params['fade'],
            $this->_params['distance'],
            $this->_params['distance']
        );

        $shadow->compositeImage(
            $this->_image->imagick, Imagick::COMPOSITE_OVER, 0, 0
        );

        if ($this->_params['padding']) {
            $shadow->borderImage(
                $this->_params['background'],
                $this->_params['padding'],
                $this->_params['padding']
            );
        }
        $this->_image->imagick->clear();
        $this->_image->imagick->addImage($shadow);
        $shadow->destroy();

        $this->_image->clearGeometry();

        return true;
    }
}
