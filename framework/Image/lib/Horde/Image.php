<?php
require_once 'Horde/Util.php';
/**
 * This class defines the Horde_Image:: API, and also provides some
 * utility functions, such as generating highlights of a color.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Horde_Image
 *
 * @TODO: - Can we depend on the Util:: class or some other solution needed?
 *        - Exceptions
 */
class Horde_Image
{
    /**
     * Background color.
     *
     * @var string
     */
    protected $_background = 'white';

    /**
     * Observers.
     *
     * @var array
     */
    protected $_observers = array();

    /**
     * Capabilites of this driver.
     *
     * @var array
     */
    protected $_capabilities = array();

    /**
     * The current image data.
     *
     * @var string
     */
    protected $_data = '';

    /**
     * The current image id.
     *
     * @TODO: Do we *really* need an image id...and if so, we can make the
     *        parameter optional in the methods that take one?
     *
     * @var string
     */
    protected $_id = '';

    /**
     * Logger
     */
    protected $_logger;

    // @TODO: width/height should be protected since they aren't reliable
    //        (should use ::getDimensions()) but we need a way to set them
    //        to zero from Effects... leaving public until a clearGeometry()
    //        method is implemented.
    /**
     * The current width of the image data.
     *
     * @var integer
     */
    public $_width = 0;

    /**
     * The current height of the image data.
     *
     * @var integer
     */
    public $_height = 0;

    /**
     * A directory for temporary files.
     *
     * @var string
     */
    protected $_tmpdir;

    /**
     * Array containing available Effects
     *
     * @var array
     */
    protected $_loadedEffects = array();

    /**
     * What kind of images should ImageMagick generate? Defaults to 'png'.
     *
     * @var string
     */
    protected $_type = 'png';

    /**
     * Constructor.
     *
     * @param string $rgb  The base color for generated pixels/images.
     */
    protected function __construct($params, $context = array())
    {
        //@TODO: This is a temporary BC hack until I update all new Horde_Image calls
        if (empty($context['tmpdir'])) {
            throw new InvalidArgumentException('A path to a temporary directory is required.');
        }
        $this->_tmpdir = $context['tmpdir'];
        if (isset($params['width'])) {
            $this->_width = $params['width'];
        }
        if (isset($params['height'])) {
            $this->_height = $params['height'];
        }
        if (!empty($params['type'])) {
            $this->_type = $params['type'];
        }

        if (!empty($context['logger'])) {
            $this->_logger = $context['logger'];
        }

        $this->_background = isset($params['background']) ? $params['background'] : 'white';
    }

    /**
     * Getter for the capabilities array
     *
     * @return array
     */
    public function getCapabilities()
    {
        return $this->_capabilities;
    }

    /**
     * Check the existence of a particular capability.
     *
     * @param string $capability  The capability to check for.
     *
     * @return boolean
     */
    public function hasCapability($capability)
    {
        return in_array($capability, $this->_capabilities);
    }

    /**
     * Generate image headers.
     */
    public function headers()
    {
        header('Content-type: ' . $this->getContentType());
    }

    /**
     * Return the content type for this image.
     *
     * @return string  The content type for this image.
     */
    public function getContentType()
    {
        return 'image/' . $this->_type;
    }

    /**
     * Getter for the simplified image type.
     *
     * @return string  The type of image (png, jpg, etc...)
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Calculate a lighter (or darker) version of a color.
     *
     * @static
     *
     * @param string $color   An HTML color, e.g.: #ffffcc.
     * @param string $factor  TODO
     *
     * @return string  A modified HTML color.
     */
    static public function modifyColor($color, $factor = 0x11)
    {
        $r = hexdec(substr($color, 1, 2)) + $factor;
        $g = hexdec(substr($color, 3, 2)) + $factor;
        $b = hexdec(substr($color, 5, 2)) + $factor;

        $r = min(max($r, 0), 255);
        $g = min(max($g, 0), 255);
        $b = min(max($b, 0), 255);

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate a more intense version of a color.
     *
     * @static
     *
     * @param string $color   An HTML color, e.g.: #ffffcc.
     * @param string $factor  TODO
     *
     * @return string  A more intense HTML color.
     */
    static public function moreIntenseColor($color, $factor = 0x11)
    {
        $r = hexdec(substr($color, 1, 2));
        $g = hexdec(substr($color, 3, 2));
        $b = hexdec(substr($color, 5, 2));

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
     * @static
     *
     * @param string $color  An HTML color, e.g.: #ffffcc.
     *
     * @return integer  The brightness on a scale of 0 to 255.
     */
    static public function brightness($color)
    {
        $r = hexdec(substr($color, 1, 2));
        $g = hexdec(substr($color, 3, 2));
        $b = hexdec(substr($color, 5, 2));

        return round((($r * 299) + ($g * 587) + ($b * 114)) / 1000);
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
     * Draw a shaped point at the specified (x,y) point. Useful for
     * scatter diagrams, debug points, etc. Draws squares, circles,
     * diamonds, and triangles.
     *
     * @param integer $x     The x coordinate of the point to brush.
     * @param integer $y     The y coordinate of the point to brush.
     * @param string $color  The color to brush the point with.
     * @param string $shape  What brush to use? Defaults to a square.
     */
    public function brush($x, $y, $color = 'black', $shape = 'square')
    {
        switch ($shape) {
        case 'triangle':
            $verts[0] = array('x' => $x + 3, 'y' => $y + 3);
            $verts[1] = array('x' => $x, 'y' => $y - 3);
            $verts[2] = array('x' => $x - 3, 'y' => $y + 3);
            $this->polygon($verts, $color, $color);
            break;

        case 'circle':
            $this->circle($x, $y, 3, $color, $color);
            break;

        case 'diamond':
            $verts[0] = array('x' => $x - 3, 'y' => $y);
            $verts[1] = array('x' => $x, 'y' => $y + 3);
            $verts[2] = array('x' => $x + 3, 'y' => $y);
            $verts[3] = array('x' => $x, 'y' => $y - 3);
            $this->polygon($verts, $color, $color);
            break;

        case 'square':
        default:
            $this->rectangle($x - 2, $y - 2, 4, 4, $color, $color);
            break;
        }
    }

    /**
     * Add an observer to this image. The observer will be notified
     * when the image's changes.
     */
    public function addObserver($method, $object)
    {
        $this->_observers[] = array($method, $object);
    }

    /**
     * Let observers know that something happened worth acting on.
     */
    public function notifyObservers()
    {
        for ($i = 0; $i < count($this->_observers); ++$i) {
            $obj = $this->_observers[$i][1];
            $method = $this->_observers[$i][0];
            $obj->$method($this);
        }
    }

    /**
     * Reset the image data to defaults.
     */
    public function reset()
    {
        $this->_data = '';
        $this->_id = '';
        $this->_width = null;
        $this->_height = null;
        $this->_background = 'white';
        $this->_type = 'png';
    }

    /**
     * Get the height and width of the current image data.
     *
     * @return array  An hash with 'width' containing the width,
     *                'height' containing the height of the image.
     */
    public function getDimensions()
    {
        // Check if we know it already
        if ($this->_width == 0 && $this->_height == 0) {
            $tmp = $this->toFile();
            $details = @getimagesize($tmp);
            list($this->_width, $this->_height) = $details;
            unlink($tmp);
        }

        return array('width' => $this->_width,
                     'height' => $this->_height);
    }

    /**
     * Load the image data from a string.
     *
     * @param string $id          An arbitrary id for the image.
     * @param string $image_data  The data to use for the image.
     */
    public function loadString($id, $image_data)
    {
        if ($id != $this->_id) {
            $this->reset();
            $this->_data = $image_data;
            $this->_id = $id;
        }
    }

    /**
     * Load the image data from a file.
     *
     * @param string $filename  The full path and filename to the file to load
     *                          the image data from. The filename will also be
     *                          used for the image id.
     *
     * @return mixed  True if successful or already loaded, PEAR Error if file
     *                does not exist or could not be loaded.
     */
    public function loadFile($filename)
    {
        if ($filename != $this->_id) {
            $this->reset();
            if (!file_exists($filename)) {
                return PEAR::raiseError('The image file ' . $filename . ' does not exist.');
            }
            if ($this->_data = file_get_contents($filename)) {
                $this->_id = $filename;
            } else {
                return PEAR::raiseError('Could not load the image file ' . $filename);
            }
        }

        return true;
    }

    /**
     * Ouputs image data to file.  If $data is false, outputs current
     * image data after performing any pending operations on the data.
     * If $data contains raw image data, outputs that data to file without
     * regard for $this->_data
     *
     * @param mixed  String of binary image data | false
     *
     * @return string  Path to temporary file.
     */
    public function toFile($data = false)
    {
        $tmp = Util::getTempFile('img', false, $this->_tmpdir);
        $fp = @fopen($tmp, 'wb');
        fwrite($fp, $data ? $data : $this->raw());
        fclose($fp);
        return $tmp;
    }

    /**
     * Display the current image.
     */
    public function display()
    {
        $this->headers();
        echo $this->raw();
    }

    /**
     * Returns the raw data for this image.
     *
     * @param boolean $convert  If true, the image data will be returned in the
     *                          target format, independently from any image
     *                          operations.
     *
     * @return string  The raw image data.
     */
    public function raw($convert = false)
    {
        return $this->_data;
    }

    // @TODO:  I don't see why these need to be private/protected...
    //         probably can just make them static. Right now, I think
    //         only _arcPoints is used (in gd.php)
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
    static protected function _circlePoint($degrees, $diameter)
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
    static protected function _arcPoints($r, $start, $end)
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
     * Attempts to apply requested effect to this image.  If the
     * effect cannot be found a PEAR_Error is returned.
     *
     * @param string $type    The type of effect to apply.
     * @param array $params   Any parameters for the effect.
     *
     * @return mixed  true on success | PEAR_Error on failure.
     */
    public function addEffect($type, $params)
    {
        $class = str_replace('Horde_Image_', '', get_class($this));
        $effect = Horde_Image_Effect::factory($type, $class, $params);
        if (is_a($effect, 'PEAR_Error')) {
            return $effect;
        }
        $effect->setImageObject($this);
        return $effect->apply();
    }

    /**
     * Load a list of available effects for this driver.
     */
    public function getLoadedEffects()
    {
        if (empty($this->_loadedEffects)) {
            $class = str_replace('Horde_Image_', '', get_class($this));

            // First, load the driver-agnostic Effects.
            // TODO: This will need to be revisted when directory structure
            //       changes for Horde 4.
            $path = dirname(__FILE__) . '/Image/Effect/';
            if (is_dir($path)) {
                if ($handle = opendir($path)) {
                    while (($file = readdir($handle)) !== false) {
                        if (substr($file, -4, 4) == '.php') {
                            $this->_loadedEffects[] = substr($file, 0, strlen($file) - 4);
                        }
                    }
                }
            }

            // Driver specific effects.
            $path = $path . $class;
            if (is_dir($path)) {
                if ($handle = opendir($path)) {
                    while (($file = readdir($handle)) !== false) {
                        if (substr($file, -4, 4) == '.php') {
                            $this->_loadedEffects[] = substr($file, 0, strlen($file) - 4);
                        }
                    }
                }
            }
        }

        return $this->_loadedEffects;
    }

    /**
     * Apply any effects in the effect queue.
     */
    public function applyEffects()
    {
        $this->raw();
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
     * @return mixed  Horde_Image object | PEAR_Error
     */
    static public function factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        $driver = basename($driver);
        $class = 'Horde_Image_' . $driver;
        if (!class_exists($class)) {
            if (!empty($app)) {
                include_once $GLOBALS['registry']->get('fileroot', $app) . '/lib/Image/' . $driver . '.php';
            } else {
                include_once 'Horde/Image/' . $driver . '.php';
            }
        }

        if (!empty($params['context']) && count($params['context'])) {
            $context = $params['context'];
            unset($params['context']);
        } else {
            $context = array();
        }
        if (class_exists($class)) {
            $image = new $class($params, $context);
        } else {
            $image = PEAR::raiseError('Class definition of ' . $class . ' not found.');
        }

        return $image;
    }

    public function getTmpDir()
    {
        return $this->_tmpdir;
    }

    /**
     * Utility function to zero out cached geometry information. Shouldn't
     * really be called from client code, but is needed since Effects may need
     * to clear these.
     *
     */
    public function clearGeometry()
    {
        $this->_height = 0;
        $this->_width = 0;
    }

    protected function _logDebug($message)
    {
        if (!empty($this->_logger)) {
            $this->_logger->debug($message);
        }
    }

    protected function _logErr($message)
    {
        if (!empty($this->_logger)) {
            $this->_logger->err($message);
        }
    }

    /**
     * Return point size for font
     */
    public static function getFontSize($fontsize)
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