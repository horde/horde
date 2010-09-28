<?php
/**
 * Image effect for determining the best crop based on the center of edginess.
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * Based on ideas and code by Jue Wang <jue@jueseph.com>
 * http://jueseph.com/2010/06/opticrop-usage-and-implementation/
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Imagick_SmartCrop extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *  <pre>
     *    width    - Target width
     *    height   - Target height
     * </pre>
     *
     * @var array
     */
    protected $_params = array();

    public function apply()
    {
        mt_srand(1);
        $this->_params = new Horde_Support_Array($this->_params);
       
        // Existing geometry
        $geometry = $this->_image->getDimensions();
        $w0 = $geometry['width'];
        $h0 = $geometry['height'];

        $w = $this->_params->width;
        $h = $this->_params->height;
        
        // @TODO: Parameterize these
        $r = 1;         // radius of edge filter
        $nk = 9;        // scale count: number of crop sizes to try
        $gamma = 0.2;   // edge normalization parameter -- see documentation
        
        // Target AR
        $ar = $this->_params->width / $this->_params->height;

        // Existing AR
        $ar0 = $w0 / $h0;

        $this->_logger->debug(sprintf("SmartCrop: %d x %d => %d x %d ", $w0, $h0, $w, $h));
        $this->_logger->debug('OAR: ' . $ar0);
        $this->_logger->debug('TAR: ' . $ar);


        // Compute COE
        $img = $this->_image->imagick->clone();
        $img->edgeImage($r);
        $img->modulateImage(100,0,100);
        $img->blackThresholdImage("#0f0f0f");

        $xcenter = $ycenter = $sum = 0;
        $n = 100000;
        for ($k = 0; $k < $n; $k++) {
            $i = mt_rand(0, $w0 - 1);
            $j = mt_rand(0, $h0 - 1);
            $pixel = $img->getImagePixelColor($i, $j);
            $val = $pixel->getColor();
            $val = $val['b'];
            $sum += $val;
            $xcenter = $xcenter + ($i + 1) * $val;
            $ycenter = $ycenter + ($j + 1) * $val;
        }
        $xcenter /= $sum;
        $ycenter /= $sum;
        $this->_logger->debug('COE: ' . $xcenter . 'x' . $ycenter);
        
        // crop source img to target AR
        if ($w0 / $h0 > $ar) {
            // source AR wider than target
            // crop width to target AR
            $wcrop0 = round($ar * $h0);
            $hcrop0 = $h0;
        } else {
            // crop height to target AR
            $wcrop0 = $w0;
            $hcrop0 = round($w0 / $ar);
        }

        // crop parameters for all scales and translations
        $params = array();

        // crop at different scales
        $hgap = $hcrop0 - $h;
        $hinc = ($nk == 1) ? 0 : $hgap / ($nk - 1);
        $wgap = $wcrop0 - $w;
        $winc = ($nk == 1) ? 0 : $wgap / ($nk - 1);

        // find window with highest normalized edginess
        $n = 10000;
        $maxbetanorm = 0;
        $maxfile = '';
        $maxparam = array('w' => 0,
                          'h' => 0,
                          'x' => 0,
                          'y' => 0);

        for ($k = 0; $k < $nk; $k++) {
            $hcrop = round($hcrop0 - $k * $hinc);
            $wcrop = round($wcrop0 - $k * $winc);
            $xcrop = $xcenter - $wcrop / 2;
            $ycrop = $ycenter - $hcrop / 2;
            if ($xcrop < 0) {
                $xcrop = 0;
            }
            if ($xcrop + $wcrop > $w0) {
                $xcrop = $w0 - $wcrop;
            }
            if ($ycrop < 0) {
                $ycrop = 0;
            }
            if ($ycrop+$hcrop > $h0) {
                $ycrop = $h0 - $hcrop;
            }
            $this->_logger->debug("crop: $wcrop, $hcrop, $xcrop, $ycrop");

            $beta = 0;
            for ($c = 0; $c < $n; $c++) {
                $i = mt_rand(0, $wcrop - 1);
                $j = mt_rand(0, $hcrop - 1);
                $pixel = $img->getImagePixelColor($xcrop + $i, $ycrop + $j);
                $val = $pixel->getColor();
                $beta += $val['b'];// & 0xFF;
            }

            $area = $wcrop * $hcrop;
            $betanorm = $beta / ($n * pow($area, $gamma - 1));

            // best image found, save the params
            if ($betanorm > $maxbetanorm) {
                $this->_logger->debug('Found best');
                $maxbetanorm = $betanorm;
                $maxparam['w'] = $wcrop;
                $maxparam['h'] = $hcrop;
                $maxparam['x'] = $xcrop;
                $maxparam['y'] = $ycrop;
            }
        }

        $this->_logger->debug('Cropping');
        // Crop to best
        $this->_image->imagick->cropImage($maxparam['w'],
                                          $maxparam['h'],
                                          $maxparam['x'],
                                          $maxparam['y']);
        $this->_image->imagick->scaleImage($w, $h);
        $img->destroy();
    }

}