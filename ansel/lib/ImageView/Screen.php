<?php
/**
 * ImageView to create the screen view - image sized for slideshow view.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageView_Screen extends Ansel_ImageView
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

        return true;
    }

}
