<?php
/**
 * Image effect for round image corners.
 *
 * This algorithm is from the phpThumb project available at
 * http://phpthumb.sourceforge.net and all credit (and complaints ;) for this
 * script should go to James Heinrich <info@silisoftware.com>.  Modifications
 * made to the code to fit it within the Horde framework and to adjust for our
 * coding standards.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Gd_RoundCorners extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *
     * radius - Radius of rounded corners.
     *
     * @var array
     */
    protected $_params = array('radius' => 10);

    /**
     * Apply the round_corners effect.
     *
     * @return boolean
     */
    public function apply()
    {
        // Original comments from phpThumb projet:
        // generate mask at twice desired resolution and downsample afterwards
        // for easy antialiasing mask is generated as a white double-size
        // elipse on a triple-size black background and copy-paste-resampled
        // onto a correct-size mask image as 4 corners due to errors when the
        // entire mask is resampled at once (gray edges)
        $radius_x = $radius_y = $this->_params['radius'];
        $gdimg = $this->_image->_im;
        $imgX = round($this->_image->call('imageSX', array($gdimg)));
        $imgY = round($this->_image->call('imageSY', array($gdimg)));
        $gdimg_cornermask_triple = $this->_image->create(round($radius_x * 6), round($radius_y * 6));
        $gdimg_cornermask = $this->_image->create($imgX, $imgY);
        $color_transparent = $this->_image->call('imageColorAllocate',
                                                  array($gdimg_cornermask_triple,
                                                        255,
                                                        255,
                                                        255));

        $this->_image->call('imageFilledEllipse',
                             array($gdimg_cornermask_triple,
                                   $radius_x * 3,
                                   $radius_y * 3,
                                   $radius_x * 4,
                                   $radius_y * 4,
                                   $color_transparent));

        $this->_image->call('imageFilledRectangle',
                             array($gdimg_cornermask,
                                   0,
                                   0,
                                   $imgX,
                                   $imgY,
                                   $color_transparent));

        $this->_image->call('imageCopyResampled',
                             array($gdimg_cornermask,
                                   $gdimg_cornermask_triple,
                                   0,
                                   0,
                                   $radius_x,
                                   $radius_y,
                                   $radius_x,
                                   $radius_y,
                                   $radius_x * 2,
                                   $radius_y * 2));

        $this->_image->call('imageCopyResampled',
                             array($gdimg_cornermask,
                                   $gdimg_cornermask_triple,
                                   0,
                                   $imgY - $radius_y,
                                   $radius_x,
                                   $radius_y * 3,
                                   $radius_x,
                                   $radius_y,
                                   $radius_x * 2,
                                   $radius_y * 2));

        $this->_image->call('imageCopyResampled',
                             array($gdimg_cornermask,
                                   $gdimg_cornermask_triple,
                                   $imgX - $radius_x,
                                   $imgY - $radius_y,
                                   $radius_x * 3,
                                   $radius_y * 3,
                                   $radius_x,
                                   $radius_y,
                                   $radius_x * 2,
                                   $radius_y * 2));

        $this->_image->call('imageCopyResampled',
                             array($gdimg_cornermask,
                                   $gdimg_cornermask_triple,
                                   $imgX - $radius_x,
                                   0,
                                   $radius_x * 3,
                                   $radius_y,
                                   $radius_x,
                                   $radius_y,
                                   $radius_x * 2,
                                   $radius_y * 2));

        $result = $this->_image->_applyMask($gdimg_cornermask);
        $this->_image->call('imageDestroy', array($gdimg_cornermask));
        $this->_image->call('imageDestroy', array($gdimg_cornermask_triple));
        
        return true;
    }

}