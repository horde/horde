<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Im_Border extends Horde_Image_Effect
{
    protected $_params = array('bordercolor' => 'black',
                               'borderwidth' => 1,
                               'preserve' => true);

/**
 * Image border decorator for the Horde_Image package.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
    /**
     * Draws the border.
     *
     * This draws the configured border to the provided image. Beware, that
     * every pixel inside the border clipping will be overwritten with the
     * background color.
     */
    public function apply()
    {
        $this->_image->addPostSrcOperation(sprintf(
            "-bordercolor \"%s\" %s -border %s",
            $this->_params['bordercolor'],
            (!empty($this->_params['preserve']) ? '-compose Copy' : ''),
            $this->_params['borderwidth']));

        return true;
    }

}