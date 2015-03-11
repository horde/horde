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
 * Imagick driver for the Horde_Image API.
 *
 * @author    Michael J. Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2007-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 *
 * @property-read  Imagick $imagick  The underlaying Imagick object.
 */
class Horde_Image_Imagick extends Horde_Image_Base
{
    /**
     * The underlaying Imagick object.
     *
     * @var Imagick
     */
    protected $_imagick;

    /**
     * Flag for iterator, since calling nextImage on Imagick would result in a
     * fatal error if there are no more images.
     *
     * @var boolean
     */
    private $_noMoreImages = false;

    /**
     * Capabilites of this driver.
     *
     * @var array
     */
    protected $_capabilities = array('resize',
                                     'crop',
                                     'rotate',
                                     'grayscale',
                                     'flip',
                                     'mirror',
                                     'sepia',
                                     'canvas',
                                     'multipage',
                                     'pdf');
    /**
     * Const'r
     *
     * @see Horde_Image_Base::_construct
     */
    public function __construct($params, $context = array())
    {
        if (!Horde_Util::loadExtension('imagick')) {
            throw new Horde_Image_Exception(
                'Required PECL Imagick extension not found.'
            );
        }

        parent::__construct($params, $context);
        ini_set('imagick.locale_fix', 1);

        $this->_imagick = new Imagick();
        if (!empty($params['filename'])) {
            $this->loadFile($params['filename']);
        } elseif(!empty($params['data'])) {
            $this->loadString($params['data']);
        } else {
            $this->_width = max(array($this->_width, 1));
            $this->_height = max(array($this->_height, 1));
            try {
                $this->_imagick->newImage($this->_width, $this->_height, $this->_background);
            } catch (ImagickException $e) {
                throw new Horde_Image_Exception($e);
            }
        }

        try {
            $this->_imagick->setImageFormat($this->_type);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
    }

    /**
     * Loads the image data from a string.
     *
     * @param string $image_data  The data to use for the image.
     *
     * @throws Horde_Image_Exception
     */
    public function loadString($image_data)
    {
        parent::loadString($image_data);
        $this->_imagick->clear();
        try {
            $this->_imagick->readImageBlob($this->_data);
            $this->_imagick->setImageFormat($this->_type);
            $this->_imagick->setIteratorIndex(0);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        unset($this->_data);
    }

    /**
     * Loads the image data from a file.
     *
     * @param string $filename  The full path and filename to the file to load
     *                          the image data from.
     *
     * @throws Horde_Image_Exception
     */
    public function loadFile($filename)
    {
        // Parent function loads image data into $this->_data
        parent::loadFile($filename);
        $this->_imagick->clear();
        try {
            $this->_imagick->readImageBlob($this->_data);
            $this->_imagick->setImageFormat($this->_type);
            $this->_imagick->setIteratorIndex(0);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        unset($this->_data);
    }

    /**
     * Sets the output image type.
     *
     * @param string $type  An image type (png, jpg, etc.)
     *
     * @return string  The previous image type.
     */
    public function setType($type)
    {
        $old = parent::setType($type);
        try {
            $this->_imagick->setImageFormat($this->_type);
        } catch (ImagickException $e) {
            // Don't care about an empty wand here.
        }
        return $old;
    }

    /**
     * Returns the raw data for this image.
     *
     * @param boolean $convert  Ignored for imagick driver.
     *
     * @return string  The raw image data.
     */
    public function raw($convert = false)
    {
        try {
            $this->_imagick->stripImage();
            return $this->_imagick->getImageBlob();
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
    }

    /**
     * Resets the image data to defaults.
     */
    public function reset()
    {
        parent::reset();
        $this->_imagick->clear();
        $this->_noMoreImages = false;
    }

    /**
     * Resize current image.
     *
     * @see Horde_Image_im::resize()
     *
     * @return void
     */
    public function resize($width, $height, $ratio = true, $keepProfile = false)
    {
        try {
            if ($keepProfile) {
                $this->_imagick->resizeImage($width, $height, $ratio);
            } else {
                $this->_imagick->thumbnailImage($width, $height, $ratio);
            }
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $this->clearGeometry();
    }

    /**
     * Returns the height and width of the current image data.
     *
     * @return array  An hash with 'width' containing the width,
     *                'height' containing the height of the image.
     */
    public function getDimensions()
    {
        if ($this->_height == 0 && $this->_width == 0) {
            try {
                $size = $this->_imagick->getImageGeometry();
            } catch (ImagickException $e) {
                return array('width' => 0, 'height' => 0);
            }

            $this->_height = $size['height'];
            $this->_width = $size['width'];
        }

        return array('width' => $this->_width, 'height' => $this->_height);

    }

    /**
     * Crops the current image.
     *
     * @param integer $x1  x for the top left corner.
     * @param integer $y1  y for the top left corner.
     * @param integer $x2  x for the bottom right corner.
     * @param integer $y2  y for the bottom right corner.
     */
    public function crop($x1, $y1, $x2, $y2)
    {
        try {
            $result = $this->_imagick->cropImage($x2 - $x1, $y2 - $y1, $x1, $y1);
            $this->_imagick->setImagePage(0, 0, 0, 0);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $this->clearGeometry();
    }

    /**
     * Rotates the current image.
     *
     * @param integer $angle      The angle to rotate the image by, in the
     *                            clockwise direction.
     * @param string $background  The background color to fill any triangles.
     */
    public function rotate($angle, $background = 'white')
    {
        try {
            $this->_imagick->rotateImage($background, $angle);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $this->clearGeometry();
    }

    /**
     * Flips the current image.
     */
    public function flip()
    {
        try {
            $this->_imagick->flipImage();
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
    }

    /**
     * Mirrors the current image.
     */
    public function mirror()
    {
        try {
            $this->_imagick->flopImage();
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
    }

    /**
     * Converts the current image to grayscale.
     */
    public function grayscale()
    {
        try {
            $this->_imagick->setImageType(Imagick::IMGTYPE_GRAYSCALE);
        } catch (ImageException $e) {
            throw new Horde_Image_Exception($e);
        }
    }

    /**
     * Applies a sepia filter.
     *
     * @param integer $threshold  Extent of sepia effect.
     */
    public function sepia($threshold = 85)
    {
        try {
            $this->_imagick->sepiaToneImage($threshold);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
    }

    /**
     * Draws a text string on the image in a specified location, with the
     * specified style information.
     *
     * @TODO: Need to differentiate between the stroke (border) and the fill
     *        color, but this is a BC break, since we were just not providing a
     *        border.
     *
     * @param string $text        The text to draw.
     * @param integer $x          The left x coordinate of the start of the
     *                            text string.
     * @param integer $y          The top y coordinate of the start of the text
     *                            string.
     * @param string $font        The font identifier you want to use for the
     *                            text.
     * @param string $color       The color that you want the text displayed in.
     * @param integer $direction  An integer that specifies the orientation of
     *                            the text.
     * @param string $fontsize    Size of the font (small, medium, large, giant)
     */
    public function text(
        $string, $x, $y, $font = '', $color = 'black', $direction = 0,
        $fontsize = 'small'
    )
    {
        $fontsize = Horde_Image::getFontSize($fontsize);
        $pixel = new ImagickPixel($color);
        $draw = new ImagickDraw();
        $draw->setFillColor($pixel);
        if (!empty($font)) {
            $draw->setFont($font);
        }
        $draw->setFontSize($fontsize);
        $draw->setGravity(Imagick::GRAVITY_NORTHWEST);
        try {
            $this->_imagick->annotateImage($draw, $x, $y, $direction, $string);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Draws a circle.
     *
     * @param integer $x     The x coordinate of the centre.
     * @param integer $y     The y coordinate of the centre.
     * @param integer $r     The radius of the circle.
     * @param string $color  The line color of the circle.
     * @param string $fill   The color to fill the circle.
     */
    public function circle($x, $y, $r, $color, $fill = 'none')
    {
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($fill));
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->circle($x, $y, $r + $x, $y);
        try {
            $this->_imagick->drawImage($draw);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Draws a polygon based on a set of vertices.
     *
     * @param array $vertices  An array of x and y labeled arrays
     *                         (eg. $vertices[0]['x'], $vertices[0]['y'], ...).
     * @param string $color    The color you want to draw the polygon with.
     * @param string $fill     The color to fill the polygon.
     */
    public function polygon($verts, $color, $fill = 'none')
    {
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($fill));
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->polygon($verts);
        try {
            $this->_imagick->drawImage($draw);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Draws a rectangle.
     *
     * @param integer $x       The left x-coordinate of the rectangle.
     * @param integer $y       The top y-coordinate of the rectangle.
     * @param integer $width   The width of the rectangle.
     * @param integer $height  The height of the rectangle.
     * @param string $color    The line color of the rectangle.
     * @param string $fill     The color to fill the rectangle.
     */
    public function rectangle($x, $y, $width, $height, $color, $fill = 'none')
    {
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->setFillColor(new ImagickPixel($fill));
        $draw->rectangle($x, $y, $x + $width, $y + $height);
        try {
            $this->_imagick->drawImage($draw);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Draws a rounded rectangle.
     *
     * @param integer $x       The left x-coordinate of the rectangle.
     * @param integer $y       The top y-coordinate of the rectangle.
     * @param integer $width   The width of the rectangle.
     * @param integer $height  The height of the rectangle.
     * @param integer $round   The width of the corner rounding.
     * @param string  $color   The line color of the rectangle.
     * @param string  $fill    The color to fill the rounded rectangle with.
     */
    public function roundedRectangle(
        $x, $y, $width, $height, $round, $color, $fill
    )
    {
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->setFillColor(new ImagickPixel($fill));
        $draw->roundRectangle($x, $y, $x + $width, $y + $height, $round, $round);
        try {
            $this->_imagick->drawImage($draw);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Draws a line.
     *
     * @param integer $x0    The x coordinate of the start.
     * @param integer $y0    The y coordinate of the start.
     * @param integer $x1    The x coordinate of the end.
     * @param integer $y1    The y coordinate of the end.
     * @param string $color  The line color.
     * @param string $width  The width of the line.
     */
    public function line($x0, $y0, $x1, $y1, $color = 'black', $width = 1)
    {
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->setStrokeWidth($width);
        $draw->line($x0, $y0, $x1, $y1);
        try {
            $this->_imagick->drawImage($draw);
        } catch (ImagickException $e) {
            throw Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Draws a dashed line.
     *
     * @param integer $x0           The x co-ordinate of the start.
     * @param integer $y0           The y co-ordinate of the start.
     * @param integer $x1           The x co-ordinate of the end.
     * @param integer $y1           The y co-ordinate of the end.
     * @param string $color         The line color.
     * @param string $width         The width of the line.
     * @param integer $dash_length  The length of a dash on the dashed line.
     * @param integer $dash_space   The length of a space in the dashed line.
     */
    public function dashedLine(
        $x0, $y0, $x1, $y1, $color = 'black', $width = 1, $dash_length = 2,
        $dash_space = 2
    )
    {
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->setStrokeWidth($width);
        $draw->setStrokeDashArray(array($dash_length, $dash_space));
        $draw->line($x0, $y0, $x1, $y1);
        try {
            $this->_imagick->drawImage($draw);
        } catch (ImageException $e) {
            throw new Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Draws a polyline (a non-closed, non-filled polygon) based on a set of
     * vertices.
     *
     * @param array $vertices  An array of x and y labeled arrays
     *                         (eg. $vertices[0]['x'], $vertices[0]['y'], ...).
     * @param string $color    The color you want to draw the line with.
     * @param string $width    The width of the line.
     */
    public function polyline($verts, $color, $width = 1)
    {
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->setStrokeWidth($width);
        $draw->setFillColor(new ImagickPixel('none'));
        $draw->polyline($verts);
        try {
            $this->_imagick->drawImage($draw);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Draws an arc.
     *
     * @param integer $x      The x coordinate of the centre.
     * @param integer $y      The y coordinate of the centre.
     * @param integer $r      The radius of the arc.
     * @param integer $start  The start angle of the arc.
     * @param integer $end    The end angle of the arc.
     * @param string $color   The line color of the arc.
     * @param string $fill    The fill color of the arc (defaults to none).
     */
    public function arc(
        $x, $y, $r, $start, $end, $color = 'black', $fill = 'none'
    )
    {
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->setFillColor(new ImagickPixel($fill));
        $points = Horde_Image::arcPoints($r, $start, $end);
        $points['x1'] += $x;
        $points['x2'] += $x;
        $points['x3'] += $x;
        $points['y1'] += $y;
        $points['y2'] += $y;
        $points['y3'] += $y;
        $draw->arc($x - $r, $y - $r, $x + $r, $y + $r, $start, $end);

        // If filled, draw the outline.
        if (!empty($fill)) {
            $mid = round(($start + $end) / 2);
            list($x1, $y1) = Horde_Image::circlePoint($start, $r * 2);
            list($x2, $y2) = Horde_Image::circlePoint($mid, $r * 2);
            list($x3, $y3) = Horde_Image::circlePoint($end, $r * 2);

            $verts = array(
                array('x' => $x + round($x3), 'y' => $y + round($y3)),
                array('x' => $x, 'y' => $y),
                array('x' => $x + round($x1), 'y' => $y + round($y1))
            );

            if ($mid > 90) {
                $verts1 = array(
                    array('x' => $x + round($x2), 'y' => $y + round($y2)),
                    array('x' => $x, 'y' => $y),
                    array('x' => $x + round($x1), 'y' => $y + round($y1))
                );
                $verts2 = array(
                    array('x' => $x + round($x3), 'y' => $y + round($y3)),
                    array('x' => $x, 'y' => $y),
                    array('x' => $x + round($x2), 'y' => $y + round($y2))
                );

                $this->polygon($verts1, $fill, $fill);
                $this->polygon($verts2, $fill, $fill);
            } else {
                $this->polygon($verts, $fill, $fill);
            }

            $this->polyline($verts, $color);
        }
        try {
            $this->_imagick->drawImage($draw);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $draw->destroy();
    }

    /**
     * Applies any effects in the effect queue.
     */
    public function applyEffects()
    {
        // noop for this driver.
    }

    /**
     */
    public function __get($property)
    {
        switch ($property) {
        case 'imagick':
            return $this->_imagick;
        }
    }

    /**
     * Utility function to wrap Imagick::borderImage.
     *
     * Use when you don't want to replace all pixels in the clipping area with
     * the border color i.e.  you want to "frame" the existing image. Preserves
     * transparency etc.
     *
     * @param Imagick &$image  The Imagick object to border.
     * @param string $color    The border color.
     * @param integer $width   The image width including the border.
     * @param integer $height  The image height including the border.
     */
    public static function frameImage(&$image, $color, $width, $height)
    {
        // Need to jump through these hoops in order to preserve any
        // transparency.
        try {
            $border = $image->clone();
            $border->borderImage(new ImagickPixel($color), $width, $height);
            $border->compositeImage($image, Imagick::COMPOSITE_COPY, $width, $height);
            $image->clear();
            $image->addImage($border);
        } catch (ImagickException $e) {
            throw new Horde_Image_Exception($e);
        }
        $border->destroy();
    }

    /**
     * Resets the Imagick iterator to the first image in the set.
     */
    public function rewind()
    {
        $this->_logDebug('Horde_Image_Imagick#rewind');
        $this->_imagick->setFirstIterator();
        $this->_noMoreImages = false;
    }

    /**
     * Returns the current image from the internal iterator.
     *
     * @return Horde_Image_Imagick
     */
    public function current()
    {
        $this->_logDebug('Horde_Image_Imagick#current');
        $params = array('data' => $this->raw());
        $image = new Horde_Image_Imagick($params, $this->_context);
        return $image;
    }

    /**
     * Returns the index of the internal iterator.
     *
     * @return integer
     */
    public function key()
    {
        $this->_logDebug('Horde_Image_Imagick#key: ' . $this->_imagick->getIteratorIndex());
        return $this->_imagick->getIteratorIndex();
    }

    /**
     * Advances the iterator.
     *
     * @return Horde_Image_Imagick
     */
    public function next()
    {
        if ($this->_imagick->hasNextImage()) {
            $this->_imagick->nextImage();
            return $this->current();
        } else {
            $this->_noMoreImages = true;
            return false;
        }
    }

    /**
     * Deterimines if the current iterator item is valid.
     *
     * @return boolean
     */
    public function valid()
    {
        $this->_logDebug('Horde_Image_Imagick#valid:' . print_r(!$this->_noMoreImages, true));
        return !$this->_noMoreImages;
    }

    /**
     * Returns a specific image from the pages of images.
     *
     * @param integer $index  The index to return.
     *
     * @return Horde_Image_Imagick  The requested image
     */
    public function getImageAtIndex($index)
    {
        if ($index >= $this->_imagick->getNumberImages()) {
            throw Horde_Image_Exception('Image index out of bounds.');
        }

        $currentIndex = $this->_imagick->getIteratorIndex();
        $this->_imagick->setIteratorIndex($index);
        $image = $this->current();
        $this->_imagick->setIteratorIndex($currentIndex);

        return $image;
    }

    /**
     * Returns the number of image pages available in the image object.
     *
     * @return integer  The number of images.
     */
    public function getImagePageCount()
    {
        return $this->_imagick->getNumberImages();
    }
 }
