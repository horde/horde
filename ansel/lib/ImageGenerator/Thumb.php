<?php
/**
 * ImageGenerator to create the thumb view (plain, resized thumbnails).
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageGenerator_Thumb extends Ansel_ImageGenerator
{
    /**
     *
     * @return Horde_Image
     */
    protected function _create()
    {
        $this->_image->resize(min($GLOBALS['conf']['thumbnail']['width'], $this->_dimensions['width']),
                              min($GLOBALS['conf']['thumbnail']['height'], $this->_dimensions['height']),
                              true);
        return $this->_image->getHordeImage();
    }

}
