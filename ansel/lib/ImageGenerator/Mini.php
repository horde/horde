<?php
/**
 * ImageGenerator to create the mini view.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator_Mini extends Ansel_ImageGenerator
{
    /**
     *
     * @return Horde_Image
     */
    protected function _create()
    {
        $this->_image->resize(min(50, $this->_dimensions['width']),
                                      min(50, $this->_dimensions['height']),
                                      true);

        return $this->_image->getHordeImage();
    }

}
