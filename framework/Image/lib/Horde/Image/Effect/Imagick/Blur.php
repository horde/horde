<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */

/**
 * Blur image effect.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Imagick_Blur extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *   - factor: (integer) Blur strength.
     *
     * @var array
     */
    protected $_params = array(
        'factor' => 3,
    );

    /**
     * Applies the effect.
     */
    public function apply()
    {
        try {
            $this->_image->imagick->blurImage(
                0,
                0.75 * $this->_params['factor'] ** 2 - 0.25 * $this->_params['factor'] + 1
            );
        } catch (Imagick_Exception $e) {
            throw new Horde_Image_Exception($e);
        }
    }
}
