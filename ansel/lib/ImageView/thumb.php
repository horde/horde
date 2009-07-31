<?php
/**
 * ImageView to create the thumb view (plain, resized thumbnails).
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageView_thumb extends Ansel_ImageView {

    function _create()
    {
        return $this->_image->_image->resize(min($GLOBALS['conf']['thumbnail']['width'], $this->_dimensions['width']),
                                             min($GLOBALS['conf']['thumbnail']['height'], $this->_dimensions['height']),
                                             true);
    }

}
