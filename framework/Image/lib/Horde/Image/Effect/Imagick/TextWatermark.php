<?php
/**
 * Image effect for watermarking images with text for the im driver..
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Imagick_TextWatermark extends Horde_Image_Effect
{
    /**
     * Valid parameters for watermark effects:
     *
     *   text (required)  - The text of the watermark.
     *   halign           - The horizontal placement
     *   valign           - The vertical placement
     *   font             - The font name or family to use
     *   fontsize         - The size of the font to use
     *                      (small, medium, large, giant)
     *
     * @var array
     */
    protected $_params = array('halign' => 'right',
                               'valign' => 'bottom',
                               'font' => 'courier',
                               'fontsize' => 'small');

    /**
     * Add the watermark
     *
     */
    public function apply()
    {
        /* Determine placement on image */
        switch ($this->_params['valign']) {
        case 'bottom':
            $v = 'south';
            break;
        case 'center':
            $v = 'center';
            break;
        default:
            $v = 'north';
        }

        switch ($this->_params['halign']) {
        case 'right':
            $h = 'east';
            break;
        case 'center':
            $h = 'center';
            break;
        default:
            $h = 'west';

        }
        if (($v == 'center' && $h != 'center') ||
            ($v == 'center' && $h == 'center')) {
            $gravity = $h;
        } elseif ($h == 'center' && $v != 'center') {
            $gravity = $v;
        } else {
            $gravity = $v . $h;
        }
        /* Determine font point size */
        $point = $this->_image->getFontSize($this->_params['fontsize']);

        //@TODO:
        throw new Horde_Image_Exception('Not Yet Implemented.');
    }

}