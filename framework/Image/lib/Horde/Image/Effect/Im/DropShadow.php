<?php
/**
 * Image effect for adding a drop shadow.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
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
    protected $_params = array('distance' => 5, // This is used as the x and y offset
                               'width' => 2,
                               'hexcolor' => '000000',
                               'angle' => 215,
                               'fade' => 3, // Sigma value
                               'padding' => 0,
                               'background' => 'none');

    /**
     * Apply the effect.
     *
     * @return mixed true
     */
    public function apply()
    {
        $size = $this->_image->getDimensions();
        $this->_image->addPostSrcOperation('\( +clone -background black -shadow 80x' . $this->_params['fade'] . '+' . $this->_params['distance'] . '+' . $this->_params['distance'] . ' \) +swap -background none -flatten +repage -bordercolor ' . $this->_params['background'] . ' -border ' . $this->_params['padding']);
        $this->_image->clearGeometry();

        return true;
    }

}