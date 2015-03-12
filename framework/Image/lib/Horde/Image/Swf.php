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

/**
 * This class implements the Horde_Image API for SWF, using the PHP Ming
 * extension.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Image
 */
class Horde_Image_Swf extends Horde_Image_Base
{
    /**
     * Capabilites of this driver.
     *
     * @var string[]
     */
    protected $_capabilities = array(
        'canvas',
        'circle',
        'dashedLine',
        'line',
        'polygon',
        'polyline',
        'rectangle',
        'roundedRectangle',
        'text',
    );

    /**
     * SWF root movie.
     *
     * @var resource
     */
    protected $_movie;

    /**
     * Constructor.
     *
     * @see Horde_Image_Base::_construct
     */
    public function __construct($params, $context = array())
    {
        parent::__construct($params, $context);

        $this->_movie = new SWFMovie();
        $this->_movie->setDimension($this->_width, $this->_height);

        $color = Horde_Image::getRGB($this->_background);
        $this->_movie->setBackground($color[0], $color[1], $color[2]);
        $this->_movie->setRate(30);
    }

    /**
     * Returns the MIME type for this image.
     *
     * @return string  The MIME type for this image.
     */
    public function getContentType()
    {
        return 'application/x-shockwave-flash';
    }

    /**
     * Displays the current image.
     */
    public function display()
    {
        $this->headers();
        $this->_movie->output();
    }

    /**
     * Returns the raw data for this image.
     *
     * @return string  The raw image data.
     */
    public function raw()
    {
        ob_start();
        $this->_movie->output();
        $data = ob_get_clean();
        return $data;
    }

    /**
     * Creates a color that can be accessed in this object.
     *
     * When a color is set, the rgba values are returned in an array.
     *
     * @param string $name  The name of the color.
     *
     * @return array  The red, green, blue, alpha values of the color.
     */
    public function allocateColor($name)
    {
        list($r, $g, $b) = Horde_Image::getRGB($name);
        return array('red' => $r, 'green' => $g, 'blue' => $b, 'alpha' => 255);
    }

    /**
     * Translates font names.
     *
     * @param string $font  A font name.
     *
     * @return string  An SWF font name.
     */
    public function getFont($font)
    {
        switch ($font) {
        case 'sans-serif':
            return '_sans';
        case 'serif':
            return '_serif';
        case 'monospace':
            return '_typewriter';
        default:
            return $font;
        }
    }

    /**
     * Draws a text string on the image in a specified location, with the
     * specified style information.
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
        $string, $x, $y, $font = 'monospace', $color = 'black', $direction = 0
    )
    {
        $color = $this->allocateColor($color);

        $text = new SWFTextField(SWFTEXTFIELD_NOEDIT);
        $text->setColor(
            $color['red'], $color['green'], $color['blue'], $color['alpha']
        );
        $text->setFont(new SWFBrowserFont($this->getFont($font)));
        $text->addString($string);

        $t = $this->_movie->add($text);
        $t->moveTo($x, $y);
        $t->rotate($direction);
    }

    /**
     * Draws a circle.
     *
     * @param integer $x      The x co-ordinate of the centre.
     * @param integer $y      The y co-ordinate of the centre.
     * @param integer $r      The radius of the circle.
     * @param string  $color  The line color of the circle.
     * @param string  $fill   The color to fill the circle.
     */
    public function circle($x, $y, $r, $color, $fill = 'none')
    {
        $s = new SWFShape();
        $color = $this->allocateColor($color);
        $s->setLine(
            1, $color['red'], $color['green'], $color['blue'], $color['alpha']
        );

        if ($fill != 'none') {
            $fillColor = $this->allocateColor($fill);
            $f = $s->addFill(
                $fillColor['red'],
                $fillColor['green'],
                $fillColor['blue'],
                $fillColor['alpha']
            );
            $s->setRightFill($f);
        }

        $a = $r * 0.414213562; // = tan(22.5 deg)
        $b = $r * 0.707106781; // = sqrt(2)/2 = sin(45 deg)

        $s->movePenTo($x + $r, $y);

        $s->drawCurveTo($x + $r, $y - $a, $x + $b, $y - $b);
        $s->drawCurveTo($x + $a, $y - $r, $x, $y - $r);
        $s->drawCurveTo($x - $a, $y - $r, $x - $b, $y - $b);
        $s->drawCurveTo($x - $r, $y - $a, $x - $r, $y);
        $s->drawCurveTo($x - $r, $y + $a, $x - $b, $y + $b);
        $s->drawCurveTo($x - $a, $y + $r, $x, $y + $r);
        $s->drawCurveTo($x + $a, $y + $r, $x + $b, $y + $b);
        $s->drawCurveTo($x + $r, $y + $a, $x + $r, $y);

        $this->_movie->add($s);
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
        $color = $this->allocateColor($color);

        if (!is_array($color) || !is_array($verts) || (sizeof($verts) <= 2)) {
            return;
        }

        $shape = new SWFShape();
        $shape->setLine(
            1, $color['red'], $color['green'], $color['blue'], $color['alpha']
        );

        if ($fill != 'none') {
            $fillColor = $this->allocateColor($fill);
            $f = $shape->addFill(
                $fillColor['red'],
                $fillColor['green'],
                $fillColor['blue'],
                $fillColor['alpha']
            );
            $shape->setRightFill($f);
        }

        $first_done = false;
        foreach ($verts as $value) {
            if (!$first_done) {
                $shape->movePenTo($value['x'], $value['y']);
                $first_done = true;
                $first_x = $value['x'];
                $first_y = $value['y'];
            }
            $shape->drawLineTo($value['x'], $value['y']);
        }
        $shape->drawLineTo($first_x, $first_y);

        $this->_movie->add($shape);
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
        $verts[0] = array('x' => $x, 'y' => $y);
        $verts[1] = array('x' => $x + $width, 'y' => $y);
        $verts[2] = array('x' => $x + $width, 'y' => $y + $height);
        $verts[3] = array('x' => $x, 'y' => $y + $height);

        $this->polygon($verts, $color, $fill);
    }

    /**
     * Draws a rounded rectangle.
     *
     * @param integer $x       The left x-coordinate of the rectangle.
     * @param integer $y       The top y-coordinate of the rectangle.
     * @param integer $width   The width of the rectangle.
     * @param integer $height  The height of the rectangle.
     * @param integer $round   The width of the corner rounding.
     * @param string $color    The line color of the rectangle.
     * @param string $fill     The color to fill the rectangle.
     */
    public function roundedRectangle(
        $x, $y, $width, $height, $round, $color = 'black', $fill = 'none'
    )
    {
        if ($round <= 0) {
            // Optimize out any calls with no corner rounding.
            return $this->rectangle($x, $y, $width, $height, $color, $fill);
        }

        $s = new SWFShape();
        $color = $this->allocateColor($color);
        $s->setLine(
            1, $color['red'], $color['green'], $color['blue'], $color['alpha']
        );

        if ($fill != 'none') {
            $fillColor = $this->allocateColor($fill);
            $f = $s->addFill(
                $fillColor['red'],
                $fillColor['green'],
                $fillColor['blue'],
                $fillColor['alpha']
            );
            $s->setRightFill($f);
        }

        // Set corner points to avoid lots of redundant math.
        $x1 = $x + $round;
        $y1 = $y + $round;

        $x2 = $x + $width - $round;
        $y2 = $y + $round;

        $x3 = $x + $width - $round;
        $y3 = $y + $height - $round;

        $x4 = $x + $round;
        $y4 = $y + $height - $round;

        // Draw the upper left corner.
        $s->movePenTo($x1, $y2);
        $s->drawArc($round, 270, 360);

        // Connect the top left and right curves.
        $s->movePenTo($x1, $y);
        $s->drawLineTo($x2, $y);

        // Draw the upper right corner.
        $s->movePenTo($x2, $y2);
        $s->drawArc($round, 0, 90);

        // Connect the top right and lower right curves.
        $s->movePenTo($x + $width, $y2);
        $s->drawLineTo($x + $width, $y3);

        // Draw the lower right corner.
        $s->movePenTo($x3, $y3);
        $s->drawArc($round, 90, 180);

        // Connect the bottom right and bottom left curves.
        $s->movePenTo($x3, $y + $height);
        $s->drawLineTo($x4, $y + $height);

        // Draw the lower left corner.
        $s->movePenTo($x4, $y4);
        $s->drawArc($round, 180, 270);

        // Connect the bottom left and top left curves.
        $s->movePenTo($x, $y4);
        $s->drawLineTo($x, $y1);

        $this->_movie->add($s);
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
    public function line($x1, $y1, $x2, $y2, $color = 'black', $width = 1)
    {
        $color = $this->allocateColor($color);
        if (!is_array($color)) {
            return;
        }

        $shape = new SWFShape();
        $shape->setLine(
            $width,
            $color['red'],
            $color['green'],
            $color['blue'],
            $color['alpha']
        );
        $shape->movePenTo($x1, $y1);
        $shape->drawLineTo($x2, $y2);

        $this->_movie->add($shape);
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
        // Get the length of the line in pixels.
        $line_length = max(
            ceil(sqrt(pow(($x1 - $x0), 2) + pow(($y1 - $y0), 2))),
            2
        );

        $cosTheta = ($x1 - $x0) / $line_length;
        $sinTheta = ($y1 - $y0) / $line_length;
        $lastx = $x0;
        $lasty = $y0;

        // Draw the dashed line.
        for ($i = 0; $i < $line_length; $i += ($dash_length + $dash_space)) {
            $x = ($dash_length * $cosTheta) + $lastx;
            $y = ($dash_length * $sinTheta) + $lasty;

            $this->line($lastx, $lasty, $x, $y, $color);

            $lastx = $x + ($dash_space * $cosTheta);
            $lasty = $y + ($dash_space * $sinTheta);
        }
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
        $color = $this->allocateColor($color);

        $shape = new SWFShape();
        $shape->setLine(
            $width,
            $color['red'],
            $color['green'],
            $color['blue'],
            $color['alpha']
        );

        $first_done = false;
        foreach ($verts as $value) {
            if (!$first_done) {
                $shape->movePenTo($value['x'], $value['y']);
                $first_done = true;
            }
            $shape->drawLineTo($value['x'], $value['y']);
        }

        $this->_movie->add($shape);
    }

    /**
     * Draws an arc.
     *
     * @param integer $x      The x co-ordinate of the centre.
     * @param integer $y      The y co-ordinate of the centre.
     * @param integer $r      The radius of the arc.
     * @param integer $start  The start angle of the arc.
     * @param integer $end    The end angle of the arc.
     * @param string $color   The line color of the arc.
     * @param string $fill    The fill color of the arc.
     */
    public function arc(
        $x, $y, $r, $start, $end, $color = 'black', $fill = 'none'
    )
    {
        $s = new SWFShape();
        $color = $this->allocateColor($color);
        $s->setLine(
            1, $color['red'], $color['green'], $color['blue'], $color['alpha']
        );

        if ($fill != 'none') {
            $fillColor = $this->allocateColor($fill);
            $s->setRightFill(
                $fillColor['red'],
                $fillColor['green'],
                $fillColor['blue'],
                $fillColor['alpha']
            );
        }

        $pts = Horde_Image::arcPoints($r, $start, $end);
        $s->movePenTo($x, $y);
        $s->drawArc($r, $start + 90, $end + 90);
        $s->movePenTo($x, $y);
        $s->drawLineTo(round($pts['x1']) + $x, round($pts['y1']) + $y);
        $s->movePenTo($x, $y);
        $s->drawLineTo(round($pts['x2']) + $x, round($pts['y2']) + $y);

        $this->_movie->add($s);
    }

    /**
     * Draws a rectangle filled with a gradient.
     *
     * @param integer $x       The left x-coordinate of the rectangle.
     * @param integer $y       The top y-coordinate of the rectangle.
     * @param integer $width   The width of the rectangle.
     * @param integer $height  The height of the rectangle.
     * @param string $color    The outline color of the rectangle.
     * @param string $fill1    The name of the start color for the gradient.
     * @param string $fill2    The name of the end color for the gradient.
     */
    public function gradientRectangle(
        $x, $y, $width, $height, $color = 'black',
        $fill1 = 'black', $fill2 = 'white'
    )
    {
        $s = new SWFShape();

        if ($color != 'none') {
            $color = $this->allocateColor($color);
            $s->setLine(
                1,
                $color['red'],
                $color['green'],
                $color['blue'],
                $color['alpha']
            );
        }

        $fill1 = $this->allocateColor($fill1);
        $fill2 = $this->allocateColor($fill2);
        $gradient = new SWFGradient();
        $gradient->addEntry(
            0.0,
            $fill1['red'],
            $fill1['green'],
            $fill1['blue'],
            $fill1['alpha']
        );
        $gradient->addEntry(
            1.0,
            $fill2['red'],
            $fill2['green'],
            $fill2['blue'],
            $fill2['alpha']
        );

        $f = $s->addFill($gradient, SWFFILL_LINEAR_GRADIENT);
        $f->scaleTo($width / $this->_width);
        $f->moveTo($x, $y);
        $s->setRightFill($f);

        $verts[0] = array('x' => $x, 'y' => $y);
        $verts[1] = array('x' => $x + $width, 'y' => $y);
        $verts[2] = array('x' => $x + $width, 'y' => $y + $height);
        $verts[3] = array('x' => $x, 'y' => $y + $height);

        $first_done = false;
        foreach ($verts as $vert) {
            if (!$first_done) {
                $s->movePenTo($vert['x'], $vert['y']);
                $first_done = true;
                $first_x = $vert['x'];
                $first_y = $vert['y'];
            }
            $s->drawLineTo($vert['x'], $vert['y']);
        }
        $s->drawLineTo($first_x, $first_y);

        $this->_movie->add($s);
    }
}
