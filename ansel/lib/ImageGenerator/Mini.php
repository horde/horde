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
        $generator = Ansel_ImageGenerator::factory('SquareThumb', array('width' => min(50, $this->_dimensions['width']),
                                                                        'height' => min(50, $this->_dimensions['height']),
                                                                        'image' => $this->_image));
        return $generator->create();
    }

}
