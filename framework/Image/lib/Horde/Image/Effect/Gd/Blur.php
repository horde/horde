<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Philip Ronan
 * @author    Martijn Frazer <martijn@martijnfrazer.nl>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */

/**
 * Blur image effect.
 *
 * Original version from Martijn Frazer based on
 * https://stackoverflow.com/a/20264482
 *
 * @author    Philip Ronan
 * @author    Martijn Frazer <martijn@martijnfrazer.nl>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Effect_Gd_Blur extends Horde_Image_Effect
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
        // Blur factor has to be an integer.
        $blurFactor = round($this->_params['factor']);

        $img = $this->_image->_im;
        $originalWidth = imagesx($img);
        $originalHeight = imagesy($img);

        $smallestWidth = ceil($originalWidth * pow(0.5, $blurFactor));
        $smallestHeight = ceil($originalHeight * pow(0.5, $blurFactor));

        // For the first run, the previous image is the original input.
        $prevImage = $img;
        $prevWidth = $originalWidth;
        $prevHeight = $originalHeight;

        // Scale way down and gradually scale back up, blurring all the way.
        for ($i = 0; $i < $blurFactor; $i++) {
            // Determine dimensions of next image.
            $nextWidth = $smallestWidth * pow(2, $i);
            $nextHeight = $smallestHeight * pow(2, $i);

            // Resize previous image to next size.
            $nextImage = imagecreatetruecolor($nextWidth, $nextHeight);
            imagecopyresized(
                $nextImage, $prevImage,
                0, 0, 0, 0,
                $nextWidth, $nextHeight, $prevWidth, $prevHeight
            );

            // Apply blur filter.
            imagefilter($nextImage, IMG_FILTER_GAUSSIAN_BLUR);

            // Now the new image becomes the previous image for the next step.
            $prevImage = $nextImage;
            $prevWidth = $nextWidth;
            $prevHeight = $nextHeight;
        }

        // Scale back to original size and blur one more time
        imagecopyresized(
            $img, $nextImage,
            0, 0, 0, 0,
            $originalWidth, $originalHeight, $nextWidth, $nextHeight
        );
        imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);

        // Clean up
        imagedestroy($prevImage);
    }
}
