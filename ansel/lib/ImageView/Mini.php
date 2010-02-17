<?php
/**
 * ImageView to create the mini view.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageView_Mini extends Ansel_ImageView
{
    protected function _create()
    {
        $this->_image->resize(min(50, $this->_dimensions['width']),
                                      min(50, $this->_dimensions['height']),
                                      true);

        return true;
    }

}
