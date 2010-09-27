<?php
/**
 * This class implements the Horde_Image:: API for the PHP GD
 * extension.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Gd extends Horde_Image_Base
{

    /**
     * Capabilites of this driver.
     *
     * @var array
     */
    protected $_capabilities = array('resize',
                                     'crop',
                                     'rotate',
                                     'flip',
                                     'mirror',
                                     'grayscale',
                                     'sepia',
                                     'yellowize',
                                     'canvas');

    /**
     * GD Image resource for the current image data.
     *
     * @var resource
     */
    protected $_im;

    /**
     * Const'r
     *
     * @param $params
     *
     * @return Horde_Image_gd
     */
    public function __construct($params, $context = array())
    {
        parent::__construct($params, $context);
        if (!empty($params['width'])) {
            $this->_im = $this->create($this->_width, $this->_height);
            $this->call('imageFill', array($this->_im, 0, 0, $this->_allocateColor($this->_background)));
        }
    }

    public function __get($property)
    {
        switch ($property) {
        case '_im':
             return $this->_im;
        }
    }

    /**
     * Display the current image.
     */
    public function display()
    {
        $this->headers();

        return $this->call('image' . $this->_type, array($this->_im));
    }

    /**
     * Returns the raw data for this image.
     *
     * @param boolean $convert (ignored)
     *
     * @return string  The raw image data.
     */
    public function raw($convert = false)
    {
        if (!is_resource($this->_im)) {
            return '';
        }

        ob_start();
        call_user_func('image' . $this->_type, $this->_im);
        return ob_get_clean();
    }

    /**
     * Reset the image data.
     */
    public function reset()
    {
        parent::reset();
        if (is_resource($this->_im)) {
            return $this->call('imageDestroy', array($this->_im));
        }

        return true;
    }

    /**
     * Get the height and width of the current image.
     *
     * @return array  An hash with 'width' containing the width,
     *                'height' containing the height of the image.
     */
    public function getDimensions()
    {
        if (is_resource($this->_im) && $this->_width == 0 && $this->_height ==0) {
            $this->_width = $this->call('imageSX', array($this->_im));
            $this->_height = $this->call('imageSY', array($this->_im));
            return array('width' => $this->_width,
                         'height' => $this->_height);
        } else {
            return array('width' => $this->_width,
                         'height' => $this->_height);
        }
    }

    /**
     * Creates a color that can be accessed in this object. When a
     * color is set, the integer resource of it is returned.
     *
     * @param string $name  The name of the color.
     * @param int $alpha    Alpha transparency (0 - 127)
     *
     * @return integer  The resource of the color that can be passed to GD.
     */
    private function _allocateColor($name, $alpha = 0)
    {
        static $colors = array();

        if (empty($colors[$name])) {
            list($r, $g, $b) = self::getRGB($name);
            $colors[$name] = $this->call('imageColorAllocateAlpha', array($this->_im, $r, $g, $b, $alpha));
        }

        return $colors[$name];
    }

    /**
     * Get numeric font size from textual description
     *
     * @param string $font  The textual size description
     *
     * @return integer
     */
    private function _getFont($font)
    {
        switch ($font) {
        case 'tiny':
            return 1;

        case 'medium':
            return 3;

        case 'large':
            return 4;

        case 'giant':
            return 5;

        case 'small':
        default:
            return 2;
        }
    }

    /**
     * Load the image data from a string.
     *
     * @param string $image_data  The data to use for the image.
     *
     * @return void
     */
    public function loadString($image_data)
    {
        $this->_im = $this->call('imageCreateFromString', array($image_data));
    }

    /**
     * Load the image data from a file.
     *
     * @param string $filename  The full path and filename to the file to load
     *                          the image data from. The filename will also be
     *                          used for the image id.
     *
     * @return boolean
     * @throws Horde_Image_Exception
     */
    public function loadFile($filename)
    {
        $info = $this->call('getimagesize', array($filename));
        if (is_array($info)) {
            switch ($info[2]) {
            case 1:
                if (function_exists('imagecreatefromgif')) {
                    $this->_im = $this->call('imagecreatefromgif', array($filename));
                }
                break;
            case 2:
                $this->_im = $this->call('imagecreatefromjpeg', array($filename));
                break;
            case 3:
                $this->_im = $this->call('imagecreatefrompng', array($filename));
                break;
            case 15:
                if (function_exists('imagecreatefromgwbmp')) {
                    $this->_im = $this->call('imagecreatefromgwbmp', array($filename));
                }
                break;
            case 16:
                $this->_im = $this->call('imagecreatefromxbm', array($filename));
                break;
            }
        }

        if (is_resource($this->_im)) {
            return true;
        }

        $result = parent::loadFile($filename);

        $this->_im = $this->call('imageCreateFromString', array($this->_data));
    }

    /**
     * Resize the current image.
     *
     * @param integer $width      The new width.
     * @param integer $height     The new height.
     * @param boolean $ratio      Maintain original aspect ratio.
     *
     * @return boolean
     */
    public function resize($width, $height, $ratio = true)
    {
        /* Abort if we're asked to divide by zero, truncate the image
         * completely in either direction, or there is no image data.
         */
        if (!$width || !$height || !is_resource($this->_im)) {
            throw new Horde_Image_Exception('Unable to resize image.');
        }

        if ($ratio) {
            if ($width / $height > $this->call('imageSX', array($this->_im)) / $this->call('imageSY', array($this->_im))) {
                $width = $height * $this->call('imageSX', array($this->_im)) / $this->call('imageSY', array($this->_im));
            } else {
                $height = $width * $this->call('imageSY', array($this->_im)) / $this->call('imageSX', array($this->_im));
            }
        }

        $im = $this->_im;
        $this->_im = $this->create($width, $height);

        /* Reset geometry since it will change */
        $this->_width = 0;
        $this->_height = 0;

        $this->call('imageFill', array($this->_im, 0, 0, $this->call('imageColorAllocate', array($this->_im, 255, 255, 255))));
        try {
            $this->call('imageCopyResampled', array($this->_im, $im, 0, 0, 0, 0, $width, $height, $this->call('imageSX', array($im)), $this->call('imageSY', array($im))));
        } catch (Horde_Image_Exception $e) {
            $this->call('imageCopyResized', array($this->_im, $im, 0, 0, 0, 0, $width, $height, $this->call('imageSX', array($im)), $this->call('imageSY', array($im))));
        }
    }

    /**
     * Crop the current image.
     *
     * @param integer $x1  The top left corner of the cropped image.
     * @param integer $y1  The top right corner of the cropped image.
     * @param integer $x2  The bottom left corner of the cropped image.
     * @param integer $y2  The bottom right corner of the cropped image.
     */
    public function crop($x1, $y1, $x2, $y2)
    {
        $im = $this->_im;
        $this->_im = $this->create($x2 - $x1, $y2 - $y1);
        $this->_width = 0;
        $this->_height = 0;
        $this->call('imageCopy', array($this->_im, $im, 0, 0, $x1, $y1, $x2 - $x1, $y2 - $y1));
    }

    /**
     * Rotate the current image.
     *
     * @param integer $angle       The angle to rotate the image by,
     *                             in the clockwise direction
     * @param integer $background  The background color to fill any triangles
     */
    public function rotate($angle, $background = 'white')
    {
        $background = $this->_allocateColor($background);

        $this->_width = 0;
        $this->_height = 0;

        switch ($angle) {
        case '90':
            $x = $this->call('imageSX', array($this->_im));
            $y = $this->call('imageSY', array($this->_im));
            $xymax = max($x, $y);

            $im = $this->create($xymax, $xymax);
            $im = $this->call('imageRotate', array($im, 270, $background));
            $this->_im = $im;
            $im = $this->create($y, $x);
            if ($x < $y) {
                $this->call('imageCopy', array($im, $this->_im, 0, 0, 0, 0, $xymax, $xymax));
            } elseif ($x > $y) {
                $this->call('imageCopy', array($im, $this->_im, 0, 0, $xymax - $y, $xymax - $x, $xymax, $xymax));
            }
            $this->_im = $im;
            break;

        default:
            $this->_im = $this->call('imageRotate', array($this->_im, 360 - $angle, $background));
        }
    }

    /**
     * Flip the current image.
     */
    public function flip()
    {
        $x = $this->call('imageSX', array($this->_im));
        $y = $this->call('imageSY', array($this->_im));

        $im = $this->create($x, $y);
        for ($curY = 0; $curY < $y; $curY++) {
            $this->call('imageCopy', array($im, $this->_im, 0, $y - ($curY + 1), 0, $curY, $x, 1));
        }

        $this->_im = $im;
    }

    /**
     * Mirror the current image.
     */
    public function mirror()
    {
        $x = $this->call('imageSX', array($this->_im));
        $y = $this->call('imageSY', array($this->_im));

        $im = $this->create($x, $y);
        for ($curX = 0; $curX < $x; $curX++) {
            $this->call('imageCopy', array($im, $this->_im, $x - ($curX + 1), 0, $curX, 0, 1, $y));
        }

        $this->_im = $im;
    }

    /**
     * Convert the current image to grayscale.
     */
    public function grayscale()
    {
        $rateR = .229;
        $rateG = .587;
        $rateB = .114;
        $whiteness = 3;
        if ($this->call('imageIsTrueColor', array($this->_im)) === true) {
            $this->call('imageTrueColorToPalette', array($this->_im, true, 256));
        }
        $colors = min(256, $this->call('imageColorsTotal', array($this->_im)));
        for ($x = 0; $x < $colors; $x++) {
            $src = $this->call('imageColorsForIndex', array($this->_im, $x));
            $new = min(255, abs($src['red'] * $rateR + $src['green'] * $rateG + $src['blue'] * $rateB) + $whiteness);
            $this->call('imageColorSet', array($this->_im, $x, $new, $new, $new));
        }
    }

    /**
     * Sepia filter.
     *
     * Basically turns the image to grayscale and then adds some
     * defined tint on it (R += 30, G += 43, B += -23) so it will
     * appear to be a very old picture.
     *
     * @param integer $threshold  (Ignored in GD driver for now)
     */
    public function sepia($threshold = 85)
    {
        $tintR = 80;
        $tintG = 43;
        $tintB = -23;
        $rateR = .229;
        $rateG = .587;
        $rateB = .114;
        $whiteness = 3;

        if ($this->call('imageIsTrueColor', array($this->_im)) === true) {
            $this->call('imageTrueColorToPalette', array($this->_im, true, 256));
        }

        $colors = max(256, $this->call('imageColorsTotal', array($this->_im)));
        for ($x = 0; $x < $colors; $x++) {
            $src = $this->call('imageColorsForIndex', array($this->_im, $x));
            $new = min(255, abs($src['red'] * $rateR + $src['green'] * $rateG + $src['blue'] * $rateB) + $whiteness);
            $r = min(255, $new + $tintR);
            $g = min(255, $new + $tintG);
            $b = min(255, $new + $tintB);
            $this->call('imageColorSet', array($this->_im, $x, $r, $g, $b));
        }
    }

    /**
     * Yellowize filter.
     *
     * Adds a layer of yellow that can be transparent or solid. If
     * $intensityA is 255 the image will be 0% transparent (solid).
     *
     * @param integer $intensityY  How strong should the yellow (red and green) be? (0-255)
     * @param integer $intensityB  How weak should the blue be? (>= 2, in the positive limit it will be make BLUE 0)
     */
    public function yellowize($intensityY = 50, $intensityB = 3)
    {
        if ($this->call('imageIsTrueColor', array($this->_im)) === true) {
            $this->call('imageTrueColorToPalette', array($this->_im, true, 256));
        }

        $colors = max(256, $this->call('imageColorsTotal', array($this->_im)));
        for ($x = 0; $x < $colors; $x++) {
            $src = $this->call('imageColorsForIndex', array($this->_im, $x));
            $r = min($src['red'] + $intensityY, 255);
            $g = min($src['green'] + $intensityY, 255);
            $b = max(($r + $g) / max($intensityB, 2), 0);
            $this->call('imageColorSet', array($this->_im, $x, $r, $g, $b));
        }
    }

    /**
     * Draws a text string on the image in a specified location, with
     * the specified style information.
     *
     * @param string  $string     The text to draw.
     * @param integer $x          The left x coordinate of the start of the
     *                            text string.
     * @param integer $y          The top y coordinate of the start of the text
     *                            string.
     * @param string  $font       The font identifier you want to use for the
     *                            text (ignored for GD - font determined by
     *                            $fontsize).
     * @param string  $color      The color that you want the text displayed in.
     * @param integer $direction  An integer that specifies the orientation of
     *                            the text.
     * @param string  $fontsize   The font (size) to use.
     *
     * @return boolean
     */
    public function text($string, $x, $y, $font = 'monospace', $color = 'black', $direction = 0, $fontsize = 'small')
    {
        $c = $this->_allocateColor($color);
        $f = $this->_getFont($fontsize);
        switch ($direction) {
        case -90:
        case 270:
            $result = $this->call('imageStringUp', array($this->_im, $f, $x, $y, $string, $c));
            break;

        case 0:
        default:
            $result = $this->call('imageString', array($this->_im, $f, $x, $y, $string, $c));
        }

        return $result;
    }

    /**
     * Draw a circle.
     *
     * @param integer $x     The x co-ordinate of the centre.
     * @param integer $y     The y co-ordinate of the centre.
     * @param integer $r     The radius of the circle.
     * @param string $color  The line color of the circle.
     * @param string $fill   The color to fill the circle.
     */
    public function circle($x, $y, $r, $color, $fill = null)
    {
        $c = $this->_allocateColor($color);
        if (is_null($fill)) {
            $result = $this->call('imageEllipse', array($this->_im, $x, $y, $r * 2, $r * 2, $c));
        } else {
            if ($fill !== $color) {
                $fillColor = $this->_allocateColor($fill);
                $this->call('imageFilledEllipse', array($this->_im, $x, $y, $r * 2, $r * 2, $fillColor));
                $this->call('imageEllipse', array($this->_im, $x, $y, $r * 2, $r * 2, $c));
            } else {
                $this->call('imageFilledEllipse', array($this->_im, $x, $y, $r * 2, $r * 2, $c));
            }
        }
    }

    /**
     * Draw a polygon based on a set of vertices.
     *
     * @param array $vertices  An array of x and y labeled arrays
     *                         (eg. $vertices[0]['x'], $vertices[0]['y'], ...).
     * @param string $color    The color you want to draw the polygon with.
     * @param string $fill     The color to fill the polygon.
     */
    public function polygon($verts, $color, $fill = 'none')
    {
        $vertices = array();
        foreach ($verts as $vert) {
            $vertices[] = $vert['x'];
            $vertices[] = $vert['y'];
        }

        if ($fill != 'none') {
            $f = $this->_allocateColor($fill);
            $this->call('imageFilledPolygon', array($this->_im, $vertices, count($verts), $f));
        }

        if ($fill == 'none' || $fill != $color) {
            $c = $this->_allocateColor($color);
            $this->call('imagePolygon', array($this->_im, $vertices, count($verts), $c));
        }
    }

    /**
     * Draw a rectangle.
     *
     * @param integer $x       The left x-coordinate of the rectangle.
     * @param integer $y       The top y-coordinate of the rectangle.
     * @param integer $width   The width of the rectangle.
     * @param integer $height  The height of the rectangle.
     * @param string $color    The line color of the rectangle.
     * @param string $fill     The color to fill the rectangle with.
     */
    public function rectangle($x, $y, $width, $height, $color = 'black', $fill = 'none')
    {
        if ($fill != 'none') {
            $f = $this->_allocateColor($fill);
            $this->call('imageFilledRectangle', array($this->_im, $x, $y, $x + $width, $y + $height, $f));
        }

        if ($fill == 'none' || $fill != $color) {
            $c = $this->_allocateColor($color);
            $this->call('imageRectangle', array($this->_im, $x, $y, $x + $width, $y + $height, $c));
        }
    }

    /**
     * Draw a rounded rectangle.
     *
     * @param integer $x       The left x-coordinate of the rectangle.
     * @param integer $y       The top y-coordinate of the rectangle.
     * @param integer $width   The width of the rectangle.
     * @param integer $height  The height of the rectangle.
     * @param integer $round   The width of the corner rounding.
     * @param string $color    The line color of the rectangle.
     * @param string $fill     The color to fill the rounded rectangle with.
     */
    public function roundedRectangle($x, $y, $width, $height, $round, $color = 'black', $fill = 'none')
    {
        if ($round <= 0) {
            // Optimize out any calls with no corner rounding.
            return $this->rectangle($x, $y, $width, $height, $color, $fill);
        }

        $c = $this->_allocateColor($color);

        // Set corner points to avoid lots of redundant math.
        $x1 = $x + $round;
        $y1 = $y + $round;

        $x2 = $x + $width - $round;
        $y2 = $y + $round;

        $x3 = $x + $width - $round;
        $y3 = $y + $height - $round;

        $x4 = $x + $round;
        $y4 = $y + $height - $round;

        $r = $round * 2;

        // Calculate the upper left arc.
        $p1 = Horde_Image::arcPoints($round, 180, 225);
        $p2 = Horde_Image::arcPoints($round, 225, 270);

        // Calculate the upper right arc.
        $p3 = Horde_Image::arcPoints($round, 270, 315);
        $p4 = Horde_Image::arcPoints($round, 315, 360);

        // Calculate the lower right arc.
        $p5 = Horde_Image::arcPoints($round, 0, 45);
        $p6 = Horde_Image::arcPoints($round, 45, 90);

        // Calculate the lower left arc.
        $p7 = Horde_Image::arcPoints($round, 90, 135);
        $p8 = Horde_Image::arcPoints($round, 135, 180);

        // Draw the corners - upper left, upper right, lower right,
        // lower left.
        $this->call('imageArc', array($this->_im, $x1, $y1, $r, $r, 180, 270, $c));
        $this->call('imageArc', array($this->_im, $x2, $y2, $r, $r, 270, 360, $c));
        $this->call('imageArc', array($this->_im, $x3, $y3, $r, $r, 0, 90, $c));
        $this->call('imageArc', array($this->_im, $x4, $y4, $r, $r, 90, 180, $c));

        // Draw the connecting sides - top, right, bottom, left.
        $this->call('imageLine', array($this->_im, $x1 + $p2['x2'], $y1 + $p2['y2'], $x2 + $p3['x1'], $y2 + $p3['y1'], $c));
        $this->call('imageLine', array($this->_im, $x2 + $p4['x2'], $y2 + $p4['y2'], $x3 + $p5['x1'], $y3 + $p5['y1'], $c));
        $this->call('imageLine', array($this->_im, $x3 + $p6['x2'], $y3 + $p6['y2'], $x4 + $p7['x1'], $y4 + $p7['y1'], $c));
        $this->call('imageLine', array($this->_im, $x4 + $p8['x2'], $y4 + $p8['y2'], $x1 + $p1['x1'], $y1 + $p1['y1'], $c));


        if ($fill != 'none') {
            $f = $this->_allocateColor($fill);
            $this->call('imageFillToBorder', array($this->_im, $x + ($width / 2), $y + ($height / 2), $c, $f));
        }
    }

    /**
     * Draw a line.
     *
     * @param integer $x0    The x co-ordinate of the start.
     * @param integer $y0    The y co-ordinate of the start.
     * @param integer $x1    The x co-ordinate of the end.
     * @param integer $y1    The y co-ordinate of the end.
     * @param string $color  The line color.
     * @param string $width  The width of the line.
     */
    public function line($x1, $y1, $x2, $y2, $color = 'black', $width = 1)
    {
        $c = $this->_allocateColor($color);

        // Don't need to do anything special for single-width lines.
        if ($width == 1) {
            $this->call('imageLine', array($this->_im, $x1, $y1, $x2, $y2, $c));
        } elseif ($x1 == $x2) {
            // For vertical lines, we can just draw a vertical
            // rectangle.
            $left = $x1 - floor(($width - 1) / 2);
            $right = $x1 + floor($width / 2);
            $this->call('imageFilledRectangle', array($this->_im, $left, $y1, $right, $y2, $c));
        } elseif ($y1 == $y2) {
            // For horizontal lines, we can just draw a horizontal
            // filled rectangle.
            $top = $y1 - floor($width / 2);
            $bottom = $y1 + floor(($width - 1) / 2);
            $this->call('imageFilledRectangle', array($this->_im, $x1, $top, $x2, $bottom, $c));
        } else {
            // Angled lines.

            // Make sure that the end points of the line are
            // perpendicular to the line itself.
            $a = atan2($y1 - $y2, $x2 - $x1);
            $dx = (sin($a) * $width / 2);
            $dy = (cos($a) * $width / 2);

            $verts = array($x2 + $dx, $y2 + $dy, $x2 - $dx, $y2 - $dy, $x1 - $dx, $y1 - $dy, $x1 + $dx, $y1 + $dy);
            $this->call('imageFilledPolygon', array($this->_im, $verts, count($verts) / 2, $c));
        }
    }

    /**
     * Draw a dashed line.
     *
     * @param integer $x0           The x co-ordinate of the start.
     * @param integer $y0           The y co-ordinate of the start.
     * @param integer $x1           The x co-ordinate of the end.
     * @param integer $y1           The y co-ordinate of the end.
     * @param string $color         The line color.
     * @param string $width         The width of the line.
     * @param integer $dash_length  The length of a dash on the dashed line
     * @param integer $dash_space   The length of a space in the dashed line
     */
    public function dashedLine($x0, $y0, $x1, $y1, $color = 'black', $width = 1, $dash_length = 2, $dash_space = 2)
    {
        $c = $this->_allocateColor($color);
        $w = $this->_allocateColor('white');

        // Set up the style array according to the $dash_* parameters.
        $style = array();
        for ($i = 0; $i < $dash_length; $i++) {
            $style[] = $c;
        }
        for ($i = 0; $i < $dash_space; $i++) {
            $style[] = $w;
        }

        $this->call('imageSetStyle', array($this->_im, $style));
        $this->call('imageSetThickness', array($this->_im, $width));
        $this->call('imageLine', array($this->_im, $x0, $y0, $x1, $y1, IMG_COLOR_STYLED));
    }

    /**
     * Draw a polyline (a non-closed, non-filled polygon) based on a
     * set of vertices.
     *
     * @param array $vertices  An array of x and y labeled arrays
     *                         (eg. $vertices[0]['x'], $vertices[0]['y'], ...).
     * @param string $color    The color you want to draw the line with.
     * @param string $width    The width of the line.
     */
    public function polyline($verts, $color, $width = 1)
    {
        $first = true;
        foreach ($verts as $vert) {
            if (!$first) {
                $this->line($lastX, $lastY, $vert['x'], $vert['y'], $color, $width);
            } else {
                $first = false;
            }
            $lastX = $vert['x'];
            $lastY = $vert['y'];
        }
    }

    /**
     * Draw an arc.
     *
     * @param integer $x      The x co-ordinate of the centre.
     * @param integer $y      The y co-ordinate of the centre.
     * @param integer $r      The radius of the arc.
     * @param integer $start  The start angle of the arc.
     * @param integer $end    The end angle of the arc.
     * @param string  $color  The line color of the arc.
     * @param string  $fill   The fill color of the arc (defaults to none).
     */
    public function arc($x, $y, $r, $start, $end, $color = 'black', $fill = null)
    {
        $c = $this->_allocateColor($color);
        if (is_null($fill)) {
            $this->call('imageArc', array($this->_im, $x, $y, $r * 2, $r * 2, $start, $end, $c));
        } else {
            if ($fill !== $color) {
                $f = $this->_allocateColor($fill);
                $this->call('imageFilledArc', array($this->_im, $x, $y, $r * 2, $r * 2, $start, $end, $f, IMG_ARC_PIE));
                $this->call('imageFilledArc', array($this->_im, $x, $y, $r * 2, $r * 2, $start, $end, $c, IMG_ARC_EDGED | IMG_ARC_NOFILL));
            } else {
                $this->call('imageFilledArc', array($this->_im, $x, $y, $r * 2, $r * 2, $start, $end, $c, IMG_ARC_PIE));
            }
        }
    }

    /**
     * Creates an image of the given size.
     * If possible the function returns a true color image.
     *
     * @param integer $width   The image width.
     * @param integer $height  The image height.
     *
     * @return resource  The image handler.
     * @throws Horde_Image_Exception
     */
    public function create($width, $height)
    {
        $result = $this->call('imageCreateTrueColor', array($width, $height));
        if (!is_resource($result)) {
            throw new Horde_Image_Exception('Could not create image.');
        }

        return $result;
    }

    /**
     * Wraps a call to a function of the gd extension.
     *
     * @param string $function  The name of the function to wrap.
     * @param array $params     An array with all parameters for that function.
     *
     * @return mixed  The result of the function call
     * @throws Horde_Image_Exception
     */
    public function call($function, $params = null)
    {
        unset($php_errormsg);
        $track = ini_set('track_errors', 1);
        $error_mask = E_ALL & ~E_WARNING & ~E_NOTICE;
        $old_reporting = error_reporting($error_mask);
        $result = call_user_func_array($function, $params);
        if ($track !== false) {
            ini_set('track_errors', $track);
        }
        error_reporting($old_reporting);
        if (!empty($php_errormsg)) {
            $error_msg = $php_errormsg;
            throw new Horde_Image_Exception($error_msg);
        }
        return $result;
    }

    /**
     * Applies the specified mask to the image.
     *
     * @param resource $gdimg_mask  The gd image resource representing the mask
     *
     * @return boolean
     */
    public function applyMask($gdimg_mask)
    {
        $imgX = round($this->call('imageSX', array($this->_im)));
        $imgY = round($this->call('imageSY', array($this->_im)));
        $gdimg_mask_resized = $this->create($imgX, $imgY);
        $result = $this->call('imageCopyResampled',
                               array($gdimg_mask_resized,
                                     $gdimg_mask,
                                     0, 0, 0, 0,
                                     $imgX,
                                     $imgY,
                                     $this->call('imageSX', array($gdimg_mask)),
                                     $this->call('imageSY', array($gdimg_mask))));

        $gdimg_mask_blendtemp = $this->create($imgX, $imgY);
        $mbtX = $this->call('imageSX', array($gdimg_mask_blendtemp));
        $mbtY = $this->call('imageSY', array($gdimg_mask_blendtemp));

        $color_background = $this->call('imageColorAllocate',
                                         array($gdimg_mask_blendtemp, 0, 0, 0));

        $this->call('imageFilledRectangle', array($gdimg_mask_blendtemp,
                                                   0,
                                                   0,
                                                   $mbtX,
                                                   $mbtY,
                                                   $color_background));

        $this->call('imageAlphaBlending',
                     array($gdimg_mask_blendtemp, false));

        $this->call('imageSaveAlpha',
                     array($gdimg_mask_blendtemp, true));

        for ($x = 0; $x < $imgX; $x++) {
            for ($y = 0; $y < $imgY; $y++) {
                $colorat = $this->call('imageColorAt', array($this->_im, $x, $y));
                $realPixel = $this->call('imageColorsForIndex', array($this->_im, $colorat));
                $colorat = $this->call('imageColorAt', array($gdimg_mask_resized, $x, $y));
                $maskPixel = Horde_Image::grayscalePixel($this->call('imageColorsForIndex', array($gdimg_mask_resized, $colorat)));
                $maskAlpha = 127 - (floor($maskPixel['red'] / 2) * (1 - ($realPixel['alpha'] / 127)));
                $newcolor = $this->_allocateColorAlpha($gdimg_mask_blendtemp,
                                                       $realPixel['red'],
                                                       $realPixel['green'],
                                                       $realPixel['blue'],
                                                       intval($maskAlpha));
                $this->call('imageSetPixel', array($gdimg_mask_blendtemp, $x, $y, $newcolor));
            }
        }
        $this->call('imageAlphaBlending', array($this->_im, false));
        $this->call('imageSaveAlpha', array($this->_im, true));
        $this->call('imageCopy', array($this->_im,
                                        $gdimg_mask_blendtemp,
                                        0, 0, 0, 0,
                                        $mbtX,
                                        $mbtY));

        $this->call('imageDestroy', array($gdimg_mask_blendtemp));

        $this->call('imageDestroy', array($gdimg_mask_resized));

        return true;
    }

    protected function _allocateColorAlpha($gdimg_hexcolorallocate, $r, $g, $b , $alpha = false)
    {
        $result = $this->call('imageColorAllocateAlpha', array($gdimg_hexcolorallocate, $r, $g, $b, intval($alpha)));
        $result = $this->call('imageColorAllocate', array($gdimg_hexcolorallocate, $r, $g, $b));
        return $result;
    }

    /**
     * Request a specific image from the collection of images.
     *
     * @param integer $index  The index to return
     *
     * @return Horde_Image_Base
     */
    public function getImageAtIndex($index)
    {
        if ($index > 0) {
            throw new Horde_Image_Exception('Image index out of bounds.');
        }
    }

    /**
     * Return the number of image pages available in the image object.
     *
     * @return integer
     */
    public function getImagePageCount()
    {
        return 1;
    }

}
