<?php
/**
 * ImageView to create the mini view.
 *
 * $Horde: ansel/lib/ImageView/mini.php,v 1.2 2007/11/14 16:11:27 chuck Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageView_mini extends Ansel_ImageView {

    function _create()
    {
        return $this->_image->_image->resize(min(50, $this->_dimensions['width']),
                                             min(50, $this->_dimensions['height']),
                                             true);
    }

}
