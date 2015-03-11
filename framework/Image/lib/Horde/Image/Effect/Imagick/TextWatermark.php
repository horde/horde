<?php
/**
 * Copyright 2007-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */

/**
 * Image effect for watermarking images with text.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2007-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Imagick_TextWatermark extends Horde_Image_Effect
{
    /**
     * Valid parameters for watermark effects:
     *   - text:     [REQUIRED] (string) The text of the watermark.
     *   - halign:   (string) The horizontal placement
     *   - valign:   (string) The vertical placement
     *   - font:     (string) The font name or family to use
     *   - fontsize: (string) The size of the font to use (small, medium,
     *               large, giant)
     *
     * @var array
     */
    protected $_params = array('halign' => 'right',
                               'valign' => 'bottom',
                               'font' => 'courier',
                               'fontsize' => 'small');

    /**
     * Applies the effect.
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
        $point = Horde_Image::getFontSize($this->_params['fontsize']);

        //@TODO:
        throw new Horde_Image_Exception('Not Yet Implemented.');
    }

}