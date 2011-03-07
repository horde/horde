<?php
/**
 * Image effect for adding a drop shadow.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
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
    protected $_params = array('distance' => 5, // This is used as the x and y offset
                               'width' => 2,
                               'hexcolor' => '000000',
                               'angle' => 215,
                               'fade' => 3, // Sigma value
                               'padding' => 0,
                               'background' => 'none');

    /**
     * Apply the effect.
     *
     * @return boolean
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
        $shadow->shadowImage(80, $this->_params['fade'],
                             $this->_params['distance'],
                             $this->_params['distance']);

        $shadow->compositeImage($this->_image->imagick, Imagick::COMPOSITE_OVER, 0, 0);

        if ($this->_params['padding']) {
            $shadow->borderImage($this->_params['background'],
                                                $this->_params['padding'],
                                                $this->_params['padding']);
        }
        $this->_image->imagick->clear();
        $this->_image->imagick->addImage($shadow);
        $shadow->destroy();

        $this->_image->clearGeometry();

        return true;
    }

}