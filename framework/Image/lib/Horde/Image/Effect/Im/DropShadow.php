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
 * Image effect for adding a drop shadow.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2007-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Im_DropShadow extends Horde_Image_Effect
{
    /**
     * Valid parameters: Most are currently ignored for the im version
     * of this effect.
     *
     * @TODO
     *
     * @var array
     */
    protected $_params = array(
        'distance' => 5, // This is used as the x and y offset
        'width' => 2,
        'hexcolor' => '000000',
        'angle' => 215,
        'fade' => 3, // Sigma value
        'padding' => 0,
        'background' => 'none'
    );

    /**
     * Applies the effect.
     */
    public function apply()
    {
        $size = $this->_image->getDimensions();
        $this->_image->addPostSrcOperation(
            '\( +clone -background black -shadow 80x' . $this->_params['fade']
            . '+' . $this->_params['distance']
            . '+' . $this->_params['distance']
            . ' \) +swap -background none -flatten +repage -bordercolor '
            . $this->_params['background']
            . ' -border ' . $this->_params['padding']
        );
        $this->_image->clearGeometry();

        return true;
    }
}
