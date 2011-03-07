<?php
/**
 * Effect for composing multiple images into a single image.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * The technique for the Polaroid-like stack using the Imagick extension is
 * credited to Mikko Koppanen and is documented at http://valokuva.org
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Imagick_PhotoStack extends Horde_Image_Effect
{
    /**
     * Valid parameters for the stack effect
     *
     * images           -    An array of Horde_Image objects to stack. Images
     *                       are stacked in a FIFO manner, so that the top-most
     *                       image is the last one in this array.
     *
     * type             -    Determines the style for the composition.
     *                       'plain' or 'polaroid' are supported.
     *
     * resize_height    -    The height that each individual thumbnail
     *                       should be resized to before composing on the image.
     *
     * padding          -    How much padding should we ensure is left around
     *                       the active image area?
     *
     * background       -    The background canvas color - this is used as the
     *                       color to set any padding to.
     *
     * bordercolor      -    If using type 'plain' this sets the color of the
     *                       border that each individual thumbnail gets.
     *
     * borderwidth      -    If using type 'plain' this sets the width of the
     *                       border on each individual thumbnail.
     *
     * offset           -    If using type 'plain' this determines the amount of
     *                       x and y offset to give each successive image when
     *                       it is placed on the top of the stack.
     *
     * @var array
     */
    protected $_params = array('type' => 'plain',
                               'resize_height' => '150',
                               'padding' => 0,
                               'background' => 'none',
                               'bordercolor' => '#333',
                               'borderwidth' => 1,
                               'borderrounding' => 10,
                               'offset' => 5);

    /**
     * Create the photo_stack
     *
     */
    public function apply()
    {
        $i = 1;
        $cnt = count($this->_params['images']);
        if ($cnt <=0) {
            throw new Horde_Image_Exception('No Images provided.');
        }
        if (!method_exists($this->_image->imagick, 'polaroidImage') ||
            !method_exists($this->_image->imagick, 'trimImage')) {
                throw new Horde_Image_Exception('Your version of Imagick is not compiled against a recent enough ImageMagick library to use the PhotoStack effect.');
        }

        $imgs = array();
        $length = 0;

        switch ($this->_params['type']) {
        case 'plain':
        case 'rounded':
            $haveBottom = false;
            // First, we need to resize the top image to get the dimensions
            // for the rest of the stack.
            $topimg = new Imagick();
            $topimg->clear();
            $topimg->readImageBlob($this->_params['images'][$cnt - 1]->raw());
            $topimg->thumbnailImage(
                $this->_params['resize_height'],
                $this->_params['resize_height'],
                true);
            if ($this->_params['type'] == 'rounded') {
                $topimg = $this->_roundBorder($topimg);
            }

            $size = $topimg->getImageGeometry();
            foreach ($this->_params['images'] as $image) {
                $imgk= new Imagick();
                $imgk->clear();
                $imgk->readImageBlob($image->raw());
                // Either resize the thumbnail to match the top image or we *are*
                // the top image already.
                if ($i++ <= $cnt) {
                    $imgk->thumbnailImage($size['width'], $size['height'], false);
                } else {
                    $imgk->destroy();
                    $imgk = $topimg->clone();
                }
                if ($this->_params['type'] == 'rounded') {
                    $imgk = $this->_roundBorder($imgk);
                } else {
                    $imgk->borderImage($this->_params['bordercolor'],
                                       $this->_params['borderwidth'],
                                       $this->_params['borderwidth']);
                }
                // Only shadow the bottom image for 'plain' stacks
                if (!$haveBottom) {
                    $shad = $imgk->clone();
                    $shad->setImageBackgroundColor(new ImagickPixel('black'));
                    $shad->shadowImage(80, 4, 0, 0);
                    $shad->compositeImage($imgk, Imagick::COMPOSITE_OVER, 0, 0);
                    $imgk->clear();
                    $imgk->addImage($shad);
                    $shad->destroy();
                    $haveBottom = true;
                }
                // Get the geometry of the image and remember the largest.
                $geo = $imgk->getImageGeometry();
                $length = max(
                    $length,
                    sqrt(pow($geo['height'], 2) + pow($geo['width'], 2)));

                $imgs[] = $imgk;
            }
            break;
        case 'polaroid':
            foreach ($this->_params['images'] as $image) {
                //@TODO: instead of doing $image->raw(), we might be able to clone
                //         the imagick object if we can do it cleanly might
                //         be faster, less memory intensive?
                $imgk= new Imagick();
                $imgk->clear();
                $imgk->readImageBlob($image->raw());
                $imgk->thumbnailImage($this->_params['resize_height'],
                                      $this->_params['resize_height'],
                                      true);
                $imgk->setImageBackgroundColor('black');
                if ($i++ == $cnt) {
                    $angle = 0;
                } else {
                    $angle = mt_rand(1, 45);
                    if (mt_rand(1, 2) % 2 === 0) {
                        $angle = $angle * -1;
                    }
                }
                $result = $imgk->polaroidImage(new ImagickDraw(), $angle);
   
                 // Get the geometry of the image and remember the largest.
                $geo = $imgk->getImageGeometry();
                $length = max(
                    $length,
                    sqrt(pow($geo['height'], 2) + pow($geo['width'], 2)));

                $imgs[] = $imgk;
            }
            break;
        }

        // Make sure the background canvas is large enough to hold it all.
        $this->_image->imagick->thumbnailImage($length * 1.5 + 20,
                                               $length * 1.5 + 20);

        // x and y offsets.
        $xo = $yo = (count($imgs) + 1) * $this->_params['offset'];
        foreach ($imgs as $image) {
            if ($this->_params['type'] == 'polaroid') {
                $xo = mt_rand(1, $this->_params['resize_height'] / 2);
                $yo = mt_rand(1, $this->_params['resize_height'] / 2);
            } elseif ($this->_params['type'] == 'plain' ||
                      $this->_params['type'] == 'rounded') {
                $xo -= $this->_params['offset'];
                $yo -= $this->_params['offset'];
            }
            $this->_image->imagick->compositeImage($image, Imagick::COMPOSITE_OVER, $xo, $yo);
            $image->removeImage();
            $image->destroy();
        }

        // Trim the canvas before resizing to keep the thumbnails as large
        // as possible.
        $this->_image->imagick->trimImage(0);
        if ($this->_params['padding'] || $this->_params['background'] != 'none') {
            $this->_image->imagick->borderImage(
                new ImagickPixel($this->_params['background']),
                $this->_params['padding'],
                $this->_params['padding']);
        }

        return true;
    }

    private function _roundBorder($image)
    {
        $context = array('tmpdir' => $this->_image->getTmpDir());
        $size = $image->getImageGeometry();
        $new = new Horde_Image_Imagick(array(), $context);
        $new->loadString($image->getImageBlob());
        $image->destroy();
        $new->addEffect('RoundCorners', array('border' => 2, 'bordercolor' => '#111'));
        $new->applyEffects();
        $return = new Imagick();
        $return->newImage($size['width'] + $this->_params['borderwidth'],
                          $size['height'] + $this->_params['borderwidth'],
                          $this->_params['bordercolor']);
        $return->setImageFormat($this->_image->getType());
        $return->clear();
        $return->readImageBlob($new->raw());

        return $return;
    }

}