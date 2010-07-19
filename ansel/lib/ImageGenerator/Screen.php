<?php
/**
 * ImageGenerator to create the screen view - image sized for slideshow view.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator_Screen extends Ansel_ImageGenerator
{
    /**
     *
     * @return boolean
     */
    protected function _create()
    {
        $this->_image->resize(min($GLOBALS['conf']['screen']['width'], $this->_dimensions['width']),
                              min($GLOBALS['conf']['screen']['height'], $this->_dimensions['height']),
                              true);

        return $this->_image->getHordeImage();
    }

}
