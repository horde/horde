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
 * Image effect for round image corners.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2007-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Im_RoundCorners extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *   - radius: (integer) Radius of rounded corners.
     *
     * @var array
     */
    protected $_params = array(
        'radius'      => 10,
        'background'  => 'none',
        'border'      => 0,
        'bordercolor' => 'none'
    );

    /**
     * Applies the effect.
     */
    public function apply()
    {
        // Get image dimensions
        $dimensions = $this->_image->getDimensions();
        $height = $dimensions['height'];
        $width = $dimensions['width'];
        $round = $this->_params['radius'];

        $this->_image->addOperation(
            "-size {$width}x{$height} xc:{$this->_params['background']} "
            . "-fill {$this->_params['background']} -draw \"matte 0,0 reset\" -tile"
        );

        $this->_image->roundedRectangle(
            round($round / 2),
            round($round / 2),
            $width - round($round / 2) - 2,
            $height - round($round / 2) - 2,
            $round + 2,
            'none',
            'white'
        );

        // Reset width/height since these might have changed
        $this->_image->clearGeometry();

        return true;
    }
}
