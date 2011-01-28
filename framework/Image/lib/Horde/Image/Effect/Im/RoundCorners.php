<?php
/**
 * Image effect for rounding image corners.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Im_RoundCorners extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *
     *  radius - Radius of rounded corners.
     *
     * @var array
     */
    protected $_params = array('radius' => 10,
                               'background' => 'none',
                               'border' => 0,
                               'bordercolor' => 'none');

    public function apply()
    {
        /* Use imagick extension if available */
        $round = $this->_params['radius'];

        // Get image dimensions
        $dimensions = $this->_image->getDimensions();
        $height = $dimensions['height'];
        $width = $dimensions['width'];

        $this->_image->addOperation("-size {$width}x{$height} xc:{$this->_params['background']} "
            . "-fill {$this->_params['background']} -draw \"matte 0,0 reset\" -tile");

        $this->_image->roundedRectangle(round($round / 2),
                                round($round / 2),
                                $width - round($round / 2) - 2,
                                $height - round($round / 2) - 2,
                                $round + 2,
                                'none',
                                'white');

        // Reset width/height since these might have changed
        $this->_image->clearGeometry();

        return true;
    }

}