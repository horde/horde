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
class Horde_Image_Effect_Imagick_PolaroidImage extends Horde_Image_Effect
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
        if (!method_exists($this->_image->imagick, 'polaroidImage') ||
            !method_exists($this->_image->imagick, 'trimImage')) {
            throw new Horde_Image_Exception('Your version of Imagick is not compiled against a recent enough ImageMagick library to use the PolaroidImage effect.');
        }

        try {
            // This determines the color of the underlying shadow.
            $this->_image->imagick->setImageBackgroundColor(
                new ImagickPixel($this->_params['shadowcolor'])
            );
            $this->_image->imagick->polaroidImage(
                new ImagickDraw(), $this->_params['angle']
            );


            // We need to create a new image to composite the polaroid over.
            // (yes, even if it's a transparent background evidently)
            $size = $this->_image->getDimensions();
            $imk = new Imagick();
            $imk->newImage(
                $size['width'], $size['height'], $this->_params['background']
            );
            $imk->setImageFormat($this->_image->getType());
            $result = $imk->compositeImage(
                $this->_image->imagick, Imagick::COMPOSITE_OVER, 0, 0
            );
            $this->_image->imagick->clear();
            $this->_image->imagick->addImage($imk);
        } catch (ImagickPixelException $e) {
            throw new Horde_Image_Exception($e);
        } catch (ImagickDrawException $e) {
            throw new Horde_Image_Exception($e);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }

        $imk->destroy();
    }
}
