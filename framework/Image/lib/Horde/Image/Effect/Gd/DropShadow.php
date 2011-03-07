<?php
/**
 * Image effect for adding a drop shadow.
 *
 * This algorithm is from the phpThumb project available at
 * http://phpthumb.sourceforge.net and all credit for this script should go to
 * James Heinrich <info@silisoftware.com>.  Modifications made to the code
 * to fit it within the Horde framework and to adjust for our coding standards.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Gd_DropShadow extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *
     * @TODO
     *
     * @var array
     */
    protected $_params = array('distance' => 5,
                               'width' => 2,
                               'hexcolor' => '000000',
                               'angle' => 215,
                               'fade' => 10);

    /**
     * Apply the drop_shadow effect.
     *
     * @return boolean
     */
    public function apply()
    {
        $distance = $this->_params['distance'];
        $width = $this->_params['width'];
        $hexcolor = $this->_params['hexcolor'];
        $angle = $this->_params['angle'];
        $fade = $this->_params['fade'];

        $width_shadow  = cos(deg2rad($angle)) * ($distance + $width);
        $height_shadow = sin(deg2rad($angle)) * ($distance + $width);
        $gdimg = $this->_image->_im;
        $imgX = $this->_image->call('imageSX', array($gdimg));
        $imgY = $this->_image->call('imageSY', array($gdimg));

        $offset['x'] = cos(deg2rad($angle)) * ($distance + $width - 1);
        $offset['y'] = sin(deg2rad($angle)) * ($distance + $width - 1);

        $tempImageWidth  = $imgX  + abs($offset['x']);
        $tempImageHeight = $imgY + abs($offset['y']);
        $gdimg_dropshadow_temp = $this->_image->create($tempImageWidth, $tempImageHeight);
        $this->_image->call('imageAlphaBlending', array($gdimg_dropshadow_temp, false));
        $this->_image->call('imageSaveAlpha', array($gdimg_dropshadow_temp, true));
        $transparent1 = $this->_image->call('imageColorAllocateAlpha', array($gdimg_dropshadow_temp, 0, 0, 0, 127));
        $this->_image->call('imageFill', array($gdimg_dropshadow_temp, 0, 0, $transparent1));
        for ($x = 0; $x < $imgX; $x++) {
            for ($y = 0; $y < $imgY; $y++) {
                $colorat = $this->_image->call('imageColorAt', array($gdimg, $x, $y));
                $PixelMap[$x][$y] = $this->_image->call('imageColorsForIndex', array($gdimg, $colorat));
            }
        }

        /* Creates the shadow */
        $r = hexdec(substr($hexcolor, 0, 2));
        $g = hexdec(substr($hexcolor, 2, 2));
        $b = hexdec(substr($hexcolor, 4, 2));

        /* Essentially masks the original image and creates the shadow */
        for ($x = 0; $x < $tempImageWidth; $x++) {
            for ($y = 0; $y < $tempImageHeight; $y++) {
                    if (!isset($PixelMap[$x][$y]['alpha']) ||
                        ($PixelMap[$x][$y]['alpha'] > 0)) {
                        if (isset($PixelMap[$x + $offset['x']][$y + $offset['y']]['alpha']) && ($PixelMap[$x + $offset['x']][$y + $offset['y']]['alpha'] < 127)) {
                            $thisColor = $this->_image->call('imageColorAllocateAlpha', array($gdimg_dropshadow_temp, $r, $g, $b, $PixelMap[$x + $offset['x']][$y + $offset['y']]['alpha']));
                            $this->_image->call('imageSetPixel',
                                                 array($gdimg_dropshadow_temp, $x, $y, $thisColor));
                        }
                    }
            }
        }
        /* Overlays the original image */
        $this->_image->call('imageAlphaBlending',
                             array($gdimg_dropshadow_temp, true));

        for ($x = 0; $x < $imgX; $x++) {
            for ($y = 0; $y < $imgY; $y++) {
                if ($PixelMap[$x][$y]['alpha'] < 127) {
                    $thisColor = $this->_image->call('imageColorAllocateAlpha', array($gdimg_dropshadow_temp, $PixelMap[$x][$y]['red'], $PixelMap[$x][$y]['green'], $PixelMap[$x][$y]['blue'], $PixelMap[$x][$y]['alpha']));
                    $this->_image->call('imageSetPixel',
                                         array($gdimg_dropshadow_temp, $x, $y, $thisColor));
                }
            }
        }

        $this->_image->call('imageSaveAlpha',
                             array($gdimg, true));
        $this->_image->call('imageAlphaBlending',
                             array($gdimg, false));

        // Merge the shadow and the original into the original.
        $this->_image->call('imageCopyResampled',
                             array($gdimg, $gdimg_dropshadow_temp, 0, 0, 0, 0, $imgX, $imgY, $this->_image->call('imageSX', array($gdimg_dropshadow_temp)), $this->_image->call('imageSY', array($gdimg_dropshadow_temp))));

        $this->_image->call('imageDestroy', array($gdimg_dropshadow_temp));

        return true;
    }

}