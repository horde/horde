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
 * Effect for creating a polaroid looking image.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2007-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Im_PolaroidImage extends Horde_Image_Effect
{
    /**
     * Valid parameters for the polaroid effect:
     *   - background: (string) The color of the image background.
     *   - angle: (integer) Angle to rotate the image.
     *   - shadowcolor: (string) The color of the image shadow.
     *
     * @var array
     */
    protected $_params = array(
        'background'  => 'none',
        'angle'       => 0,
        'shadowcolor' => 'black'
    );

    /**
     * Applies the effect.
     */
    public function apply()
    {
        // Check for im version > 6.3.2
        $this->_image->_imagick = null;
        $ver = $this->_image->getIMVersion();
        if (is_array($ver) && version_compare($ver[0], '6.3.2') >= 0) {
            $this->_image->addPostSrcOperation(sprintf(
                "-bordercolor \"#eee\" -background none -polaroid %s \( +clone -fill %s -draw 'color 0,0 reset' \) +swap +flatten",
                $this->_params['angle'],
                $this->_params['background']
            ));
        } else {
            $size = $this->_image->getDimensions();
            $this->_image->addPostSrcOperation(sprintf(
                "-bordercolor \"#eee\" -border 8 -bordercolor grey90 -border 1 -bordercolor none -background none -rotate %s \( +clone -shadow 60x1.5+1+1 -rotate 90 -wave 1x%s -rotate 90 \) +swap -rotate 90 -wave 1x%s -rotate -90 -flatten \( +clone -fill %s -draw 'color 0,0 reset ' \) +swap -flatten",
                $this->_params['angle'],
                $size['height'] * 2,
                $size['height'] * 2,
                $this->_params['background']
            ));
        }
    }
}
