<?php
/**
 * This class provides some utility functions, such as generating highlights
 * of a color as well as a factory method responsible for creating a concrete
 * Horde_Image driver.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image
{
    /**
     * Calculate a lighter (or darker) version of a color.
     *
     * @param string $color   An HTML color, e.g.: #ffffcc.
     * @param string $factor  TODO
     *
     * @return string  A modified HTML color.
     */
    static public function modifyColor($color, $factor = 0x11)
    {
        list($r, $g, $b) = self::_getColor($color);

        $r = min(max($r + $factor, 0), 255);
        $g = min(max($g + $factor, 0), 255);
        $b = min(max($b + $factor, 0), 255);

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate a more intense version of a color.
     *
     * @param string $color   An HTML color, e.g.: #ffffcc.
     * @param string $factor  TODO
     *
     * @return string  A more intense HTML color.
     */
    static public function moreIntenseColor($color, $factor = 0x11)
    {
        list($r, $g, $b) = self::_getColor($color);

        if ($r >= $g && $r >= $b) {
            $g = $g / $r;
            $b = $b / $r;

            $r += $factor;
            $g = floor($g * $r);
            $b = floor($b * $r);
        } elseif ($g >= $r && $g >= $b) {
            $r = $r / $g;
            $b = $b / $g;

            $g += $factor;
            $r = floor($r * $g);
            $b = floor($b * $g);
        } else {
            $r = $r / $b;
            $g = $g / $b;

            $b += $factor;
            $r = floor($r * $b);
            $g = floor($g * $b);
        }

        $r = min(max($r, 0), 255);
        $g = min(max($g, 0), 255);
        $b = min(max($b, 0), 255);

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Returns the brightness of a color.
     *
     * @param string $color  An HTML color, e.g.: #ffffcc.
     *
     * @return integer  The brightness on a scale of 0 to 255.
     */
    static public function brightness($color)
    {
        list($r, $g, $b) = self::_getColor($color);

        return round((($r * 299) + ($g * 587) + ($b * 114)) / 1000);
    }

    /**
     * @TODO
     */
    static public function grayscaleValue($r, $g, $b)
    {
        return round(($r * 0.30) + ($g * 0.59) + ($b * 0.11));
    }

    /**
     * @TODO
     */
    static public function grayscalePixel($originalPixel)
    {
        $gray = Horde_Image::grayscaleValue($originalPixel['red'], $originalPixel['green'], $originalPixel['blue']);
        return array('red'=>$gray, 'green'=>$gray, 'blue'=>$gray);
    }

    /**
     * Normalizes an HTML color.
     *
     * @param string $color  An HTML color, e.g.: #ffffcc or #ffc.
     *
     * @return array  Array with three elements: red, green, and blue.
     */
    static public function _getColor($color)
    {
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        if (strlen($color) == 3) {
            $color = str_repeat($color[0], 2) .
                str_repeat($color[1], 2) .
                str_repeat($color[2], 2);
        }

        return array(
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2))
        );
    }

    /**
     * Get the RGB value for a given colorname.
     *
     * @param string $colorname  The colorname
     *
     * @return array  An array of RGB values.
     */
    static public function getRGB($colorname)
    {
        require_once dirname(__FILE__) . '/Image/rgb.php';
        return isset($GLOBALS['horde_image_rgb_colors'][$colorname]) ?
            $GLOBALS['horde_image_rgb_colors'][$colorname] :
            array(0, 0, 0);
    }

    /**
     * Get the hex representation of the given colorname.
     *
     * @param string $colorname  The colorname
     *
     * @return string  The hex representation of the color.
     */
    static public function getHexColor($colorname)
    {
        require_once dirname(__FILE__) . '/Image/rgb.php';
        if (isset($GLOBALS['horde_image_rgb_colors'][$colorname])) {
            list($r, $g, $b) = $GLOBALS['horde_image_rgb_colors'][$colorname];
            return '#' . str_pad(dechex(min($r, 255)), 2, '0', STR_PAD_LEFT) . str_pad(dechex(min($g, 255)), 2, '0', STR_PAD_LEFT) . str_pad(dechex(min($b, 255)), 2, '0', STR_PAD_LEFT);
        } else {
            return 'black';
        }
    }

    /**
     * Get an x,y pair on circle, assuming center is 0,0.
     *
     * @access private
     *
     * @param double $degrees    The degrees of arc to get the point for.
     * @param integer $diameter  The diameter of the circle.
     *
     * @return array  (x coordinate, y coordinate) of the point.
     */
    static public function circlePoint($degrees, $diameter)
    {
        // Avoid problems with doubles.
        $degrees += 0.0001;

        return array(cos(deg2rad($degrees)) * ($diameter / 2),
                     sin(deg2rad($degrees)) * ($diameter / 2));
    }

    /**
     * Get point coordinates at the limits of an arc. Only valid for
     * angles ($end - $start) <= 45 degrees.
     *
     * @access private
     *
     * @param integer $r      The radius of the arc.
     * @param integer $start  The starting angle.
     * @param integer $end    The ending angle.
     *
     * @return array  The start point, end point, and anchor point.
     */
    static public function arcPoints($r, $start, $end)
    {
        // Start point.
        $pts['x1'] = $r * cos(deg2rad($start));
        $pts['y1'] = $r * sin(deg2rad($start));

        // End point.
        $pts['x2'] = $r * cos(deg2rad($end));
        $pts['y2'] = $r * sin(deg2rad($end));

        // Anchor point.
        $a3 = ($start + $end) / 2;
        $r3 = $r / cos(deg2rad(($end - $start) / 2));
        $pts['x3'] = $r3 * cos(deg2rad($a3));
        $pts['y3'] = $r3 * sin(deg2rad($a3));

        return $pts;
    }

    /**
     * Attempts to return a concrete Horde_Image instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Image subclass to
     *                       return. This is based on the storage driver
     *                       ($driver). The code is dynamically included. If
     *                       $driver is an array, then we will look in
     *                       $driver[0]/lib/Image/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return mixed  Horde_Image object
     * @throws Horde_Image_Exception
     */
    static public function factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        $driver = basename($driver);
        $class = 'Horde_Image_' . $driver;
        if (!empty($params['context']) && count($params['context'])) {
            $context = $params['context'];
            unset($params['context']);
        } else {
            $context = array();
        }
        if (class_exists($class)) {
            return new $class($params, $context);
        } else {
            throw new Horde_Image_Exception('Invalid Image driver specified: ' . $class . ' not found.');
        }
    }

    /**
     * Return point size for font
     */
    static public function getFontSize($fontsize)
    {
        switch ($fontsize) {
        case 'medium':
            $point = 18;
            break;
        case 'large':
            $point = 24;
            break;
        case 'giant':
            $point = 30;
            break;
        default:
            $point = 12;
        }

        return $point;
    }

}
