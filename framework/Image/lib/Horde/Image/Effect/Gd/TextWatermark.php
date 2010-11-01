<?php
/**
 * Image effect for watermarking images with text for the GD driver..
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Gd_TextWatermark extends Horde_Image_Effect
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
     */
    public function apply()
    {
        $color = $this->_image->call('imageColorClosest', array($this->_image->_im, 255, 255, 255));
        $shadow = $this->_image->call('imageColorClosest', array($this->_image->_im, 0, 0, 0));

        // Shadow offset in pixels.
        $drop = 1;

        // Maximum text width.
        $maxwidth = 200;

        // Amount of space to leave between the text and the image border.
        $padding = 10;

        $f = $this->_image->getFont($this->_params['fontsize']);
        $fontwidth = $this->_image->call('imageFontWidth', array($f));
        $fontheight = $this->_image->call('imageFontHeight', array($f));

        // So that shadow is not off the image with right align and bottom valign.
        $margin = floor($padding + $drop) / 2;
        if ($maxwidth) {
            $maxcharsperline = floor(($maxwidth - ($margin * 2)) / $fontwidth);
            $text = wordwrap($this->_params['text'], $maxcharsperline, "\n", 1);
        }

        // Split $text into individual lines.
        $lines = explode("\n", $text);

        switch ($this->_params['valign']) {
        case 'center':
            $y = ($this->_image->call('imageSY', array($this->_image->_im)) - ($fontheight * count($lines))) / 2;
            break;

        case 'bottom':
            $y = $this->_image->call('imageSY', array($this->_image->_im)) - (($fontheight * count($lines)) + $margin);
            break;

        default:
            $y = $margin;
            break;
        }

        switch ($this->_params['halign']) {
        case 'right':
            foreach ($lines as $line) {
                $this->_image->call('imageString', array($this->_image->_im, $f, ($this->_image->call('imageSX', array($this->_image->_im)) - $fontwidth * strlen($line)) - $margin + $drop, ($y + $drop), $line, $shadow));
                $this->_image->call('imageString', array($this->_image->_im, $f, ($this->_image->call('imageSX', array($this->_image->_im)) - $fontwidth * strlen($line)) - $margin, $y, $line, $color));
                $y += $fontheight;
            }
            break;

        case 'center':
            foreach ($lines as $line) {
                $this->_image->call('imageString', array($this->_image->_im, $f, floor(($this->_image->call('imageSX', array($this->_image->_im)) - $fontwidth * strlen($line)) / 2) + $drop, ($y + $drop), $line, $shadow));
                $this->_image->call('imageString', array($this->_image->_im, $f, floor(($this->_image->call('imageSX', array($this->_image->_im)) - $fontwidth * strlen($line)) / 2), $y, $line, $color));
                $y += $fontheight;
            }
            break;

        default:
            foreach ($lines as $line) {
                $this->_image->call('imageString', array($this->_image->_im, $f, $margin + $drop, ($y + $drop), $line, $shadow));
                $this->_image->call('imageString', array($this->_image->_im, $f, $margin, $y, $line, $color));
                $y += $fontheight;
            }
            break;
        }
    }

}