<?php
/**
 * ImageView to create the screen view - image sized for slideshow view.
 *
 * $Horde: ansel/lib/ImageView/screen.php,v 1.2 2007/11/13 06:24:11 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageView_screen extends Ansel_ImageView {

    function _create()
    {
        return $this->_image->_image->resize(min($GLOBALS['conf']['screen']['width'], $this->_dimensions['width']),
                                             min($GLOBALS['conf']['screen']['height'], $this->_dimensions['height']),
                                             true);
    }

}
