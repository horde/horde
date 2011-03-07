<?php
/**
 * Effect for creating a polaroid looking image.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Im_PolaroidImage extends Horde_Image_Effect
{
    /**
     * Valid parameters for the polaroid effect
     *
     * resize_height    -    The height that each individual thumbnail
     *                       should be resized to before composing on the image.
     *
     * background       -    The color of the image background.
     *
     * angle            -    Angle to rotate the image.
     *
     * shadowcolor      -    The color of the image shadow.
     */

    /**
     * @var array
     */
    protected $_params = array('background' => 'none',
                               'angle' => 0,
                               'shadowcolor' => 'black');

    /**
     * Create the effect
     *
     */
    public function apply()
    {
        // Check for im version > 6.3.2
        $this->_image->_imagick = null;
        $ver = $this->_image->getIMVersion();
        if (is_array($ver) && version_compare($ver[0], '6.3.2') >= 0) {
            $this->_image->addPostSrcOperation(sprintf("-bordercolor \"#eee\" -background none -polaroid %s \( +clone -fill %s -draw 'color 0,0 reset' \) +swap +flatten",
                                                          $this->_params['angle'], $this->_params['background']));
        } else {
            $size = $this->_image->getDimensions();
            $this->_image->addPostSrcOperation(sprintf("-bordercolor \"#eee\" -border 8 -bordercolor grey90 -border 1 -bordercolor none -background none -rotate %s \( +clone -shadow 60x1.5+1+1 -rotate 90 -wave 1x%s -rotate 90 \) +swap -rotate 90 -wave 1x%s -rotate -90 -flatten \( +clone -fill %s -draw 'color 0,0 reset ' \) +swap -flatten",
                                                           $this->_params['angle'], $size['height'] * 2, $size['height'] * 2, $this->_params['background']));
        }

        return true;
    }

}