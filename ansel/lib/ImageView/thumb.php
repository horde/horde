<?php
/**
 * ImageView to create the thumb view (plain, resized thumbnails).
 *
 * $Horde: ansel/lib/ImageView/thumb.php,v 1.2 2007/11/14 16:11:27 chuck Exp $
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
