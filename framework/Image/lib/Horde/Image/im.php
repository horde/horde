<?php
/**
 * This class implements the Horde_Image:: API for ImageMagick.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_im extends Horde_Image
{
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
                                     'canvas');

    /**
     * Operations to be performed before the source filename is specified on the
     * command line.
     *
     * @var array
     */
    protected $_operations = array();

    /**
     * Operations to be added after the source filename is specified on the
     * command line.
     *
     * @var array
     */
    protected $_postSrcOperations = array();

    /**
     * Reference to an Horde_Image_ImagickProxy object.
     *
     * @TODO: This needs to be public since some of the Effects need to
     *        access this and possibly null it out. This will be resolved
     *        when Imagick is pulled out into it's own driver.
     *
     * @var Imagick
     */
    public $_imagick = null;

    /**
     * An array of temporary filenames that need to be unlinked at the end of
     * processing. Use addFileToClean() from client code (Effects) to add files
     * to this array.
     *
     * @var array
     */
    protected $_toClean = array();

    /**
     * Path to the convert binary
     *
     * @string
     */
    protected $_convert = '';

    /**
     * Constructor.
     */
    public function __construct($params, $context = array())
    {
        parent::__construct($params, $context);

        if (empty($context['convert'])) {
            throw new InvalidArgumentException('A path to the convert binary is required.');
        }
        $this->_convert = $context['convert'];

        // TODO: Will be breaking out Imagick into it's own driver.

        // Imagick library doesn't play nice with outputting data for 0x0
        // images, so use 1x1 for our default if needed.
        if (Util::loadExtension('imagick')) {
            ini_set('imagick.locale_fix', 1);
            require_once 'Horde/Image/imagick.php';
            $this->_width = max(array($this->_width, 1));
            $this->_height = max(array($this->_height, 1));
            // Use a proxy for the Imagick calls to keep exception catching
            // code out of PHP4 compliant code.
            $this->_imagick = new Horde_Image_ImagickProxy($this->_width,
                                                           $this->_height,
                                                           $this->_background,
                                                           $this->_type);
            // Yea, it's wasteful to create the proxy (which creates a blank
            // image) then overwrite it if we're passing in an image, but this
            // will be fixed in Horde 4 when imagick is broken out into it's own
            // proper driver.
            if (!empty($params['filename'])) {
                $this->loadFile($params['filename']);
            } elseif(!empty($params['data'])) {
                $this->loadString(md5($params['data']), $params['data']);
            } else {
                $this->_data = $this->_imagick->getImageBlob();
            }

            $this->_imagick->setImageFormat($this->_type);
        } else {
            if (!empty($params['filename'])) {
                $this->loadFile($params['filename']);
            } elseif (!empty($params['data'])) {
                $this->loadString(md5($params['data']), $params['data']);
            } else {
                $cmd = "-size {$this->_width}x{$this->_height} xc:{$this->_background} +profile \"*\" {$this->_type}:__FILEOUT__";
                $this->executeConvertCmd($cmd);
            }
        }
    }

    /**
     * Load the image data from a string. Need to override this method
     * in order to load the imagick object if we need to.
     *
     * @TODO: This can be nuked when imagick is broken out.
     *
     * @param string $id          An arbitrary id for the image.
     * @param string $image_data  The data to use for the image.
     */
    public function loadString($id, $image_data)
    {
        parent::loadString($id, $image_data);
        if (!is_null($this->_imagick)) {
            $this->_imagick->clear();
            $this->_imagick->readImageBlob($image_data);
            $this->_imagick->setFormat($this->_type);
            $this->_imagick->setIteratorIndex(0);
        }
    }

    /**
     * Load the image data from a file. Need to override this method
     * in order to load the imagick object if we need to.
     *
     * @TODO: Nuke when imagick is broken out.
     *
     * @param string $filename  The full path and filename to the file to load
     *                          the image data from. The filename will also be
     *                          used for the image id.
     *
     * @return mixed  PEAR Error if file does not exist or could not be loaded
     *                otherwise NULL if successful or already loaded.
     */
    public function loadFile($filename)
    {
        parent::loadFile($filename);
        if (!is_null($this->_imagick)) {
            $this->_imagick->clear();
            $this->_imagick->readImageBlob($this->_data);
            $this->_imagick->setIteratorIndex(0);
        }
    }

    /**
     * Returns the raw data for this image.
     *
     * @param boolean $convert  If true, the image data will be returned in the
     *                          target format, even if no other image operations
     *                          are present, otherwise, if no operations are
     *                          present, the current raw data is returned
     *                          unmodified.
     *
     * @return string  The raw image data.
     */
    public function raw($convert = false)
    {
        // Make sure _data is sync'd with imagick object
        if (!is_null($this->_imagick)) {
            $this->_data = $this->_imagick->getImageBlob();
        }

        if (!empty($this->_data)) {
            // If there are no operations, and we already have data, don't
            // bother writing out files, just return the current data.
            if (!$convert &&
                !count($this->_operations) &&
                !count($this->_postSrcOperations)) {
                return $this->_data;
            }

            $tmpin = $this->toFile($this->_data);
        }

        // Perform convert command if needed
        if (count($this->_operations) || count($this->_postSrcOperations) || $convert) {
            $tmpout = Util::getTempFile('img', false, $this->_tmpdir);
            $command = $this->_convert . ' ' . implode(' ', $this->_operations)
                . ' "' . $tmpin . '"\'[0]\' '
                . implode(' ', $this->_postSrcOperations)
                . ' +profile "*" ' . $this->_type . ':"' . $tmpout . '" 2>&1';
            $this->_logDebug(sprintf("convert command executed by Horde_Image_im::raw(): %s", $command));
            exec($command, $output, $retval);
            if ($retval) {
                $this->_logErr(sprintf("Error running command: %s"), $command . "\n" . implode("\n", $output));
            }

            /* Empty the operations queue */
            $this->_operations = array();
            $this->_postSrcOperations = array();

            /* Load the result */
            $this->_data = file_get_contents($tmpout);

            // Keep imagick object in sync if we need to.
            // @TODO: Might be able to stop doing this once all operations
            // are doable through imagick api.
            if (!is_null($this->_imagick)) {
                $this->_imagick->clear();
                $this->_imagick->readImageBlob($this->_data);
            }
        }

        @unlink($tmpin);
        @unlink($tmpout);

        return $this->_data;
    }

    /**
     * Reset the image data.
     */
    public function reset()
    {
        parent::reset();
        $this->_operations = array();
        $this->_postSrcOperations = array();
        if (is_object($this->_imagick)) {
            $this->_imagick->clear();
        }
        $this->_width = 0;
        $this->_height = 0;
    }

    /**
     * Resize the current image. This operation takes place immediately.
     *
     * @param integer $width        The new width.
     * @param integer $height       The new height.
     * @param boolean $ratio        Maintain original aspect ratio.
     * @param boolean $keepProfile  Keep the image meta data.
     */
    public function resize($width, $height, $ratio = true, $keepProfile = false)
    {
        if (!is_null($this->_imagick)) {
            $this->_imagick->thumbnailImage($width, $height, $ratio);
        } else {
            $resWidth = $width * 2;
            $resHeight = $height * 2;
            $this->_operations[] = "-size {$resWidth}x{$resHeight}";
            if ($ratio) {
                $this->_postSrcOperations[] = (($keepProfile) ? "-resize" : "-thumbnail") . " {$width}x{$height}";
            } else {
                $this->_postSrcOperations[] = (($keepProfile) ? "-resize" : "-thumbnail") . " {$width}x{$height}!";
            }
        }
        // Reset the width and height instance variables since after resize
        // we don't know the *exact* dimensions yet (especially if we maintained
        // aspect ratio.
        // Refresh the data
        $this->raw();
        $this->_width = 0;
        $this->_height = 0;
    }

    /**
     * More efficient way of getting size if using imagick library.
     * *ALWAYS* use getDimensions() to get image geometry...instance
     * variables only cache geometry until it changes, then they go
     * to zero.
     *
     */
    public function getDimensions()
    {
        if (!is_null($this->_imagick)) {
            if ($this->_height == 0 && $this->_width == 0) {
                $size = $this->_imagick->getImageGeometry();
                if (is_a($size, 'PEAR_Error')) {
                    return $size;
                }
                $this->_height = $size['height'];
                $this->_width = $size['width'];
            }
            return array('width' => $this->_width,
                         'height' => $this->_height);
        } else {
            return parent::getDimensions();
        }
    }

    /**
     * Crop the current image.
     *
     * @param integer $x1  x for the top left corner
     * @param integer $y1  y for the top left corner
     * @param integer $x2  x for the bottom right corner of the cropped image.
     * @param integer $y2  y for the bottom right corner of the cropped image.
     */
    public function crop($x1, $y1, $x2, $y2)
    {
        if (!is_null($this->_imagick)) {
            $result = $this->_imagick->cropImage($x2 - $x1, $y2 - $y1, $x1, $y1);
            $this->_imagick->setImagePage(0, 0, 0, 0);
        } else {
            $line = ($x2 - $x1) . 'x' . ($y2 - $y1) . '+' . $x1 . '+' . $y1;
            $this->_operations[] = '-crop ' . $line . ' +repage';
            $result = true;
        }
        // Reset width/height since these might change
        $this->raw();
        $this->_width = 0;
        $this->_height = 0;

        return $result;
    }

    /**
     * Rotate the current image.
     *
     * @param integer $angle       The angle to rotate the image by,
     *                             in the clockwise direction.
     * @param integer $background  The background color to fill any triangles.
     */
    public function rotate($angle, $background = 'white')
    {
        if (!is_null($this->_imagick)) {
            return $this->_imagick->rotateImage($background, $angle);
        } else {
            $this->raw();
            $this->_operations[] = "-background $background -rotate {$angle}";
            $this->raw();
        }
        // Reset width/height since these might have changed
        $this->_width = 0;
        $this->_height = 0;
    }

    /**
     * Flip the current image.
     */
    public function flip()
    {
        if (!is_null($this->_imagick)) {
            $this->_imagick->flipImage();
        } else {
            $this->_operations[] = '-flip';
        }
    }

    /**
     * Mirror the current image.
     */
    public function mirror()
    {
        if (!is_null($this->_imagick)) {
            $this->_imagick->flopImage();
        } else {
            $this->_operations[] = '-flop';
        }
    }

    /**
     * Convert the current image to grayscale.
     */
    public function grayscale()
    {
        if (!is_null($this->_imagick)) {
            $this->_imagick->setImageColorSpace(constant('Imagick::COLORSPACE_GRAY'));
        } else {
            $this->_postSrcOperations[] = '-colorspace GRAY';
        }
    }

    /**
     * Sepia filter.
     *
     * @param integer $threshold  Extent of sepia effect.
     */
    public function sepia($threshold =  85)
    {
        if (!is_null($this->_imagick)) {
            $this->_imagick->sepiaToneImage($threshold);
        } else {
            $this->_operations[] = '-sepia-tone ' . $threshold . '%';
        }
    }

     /**
     * Draws a text string on the image in a specified location, with
     * the specified style information.
     *
     * @TODO: Need to differentiate between the stroke (border) and the fill color,
     *        but this is a BC break, since we were just not providing a border.
     *
     * @param string  $text       The text to draw.
     * @param integer $x          The left x coordinate of the start of the text string.
     * @param integer $y          The top y coordinate of the start of the text string.
     * @param string  $font       The font identifier you want to use for the text.
     * @param string  $color      The color that you want the text displayed in.
     * @param integer $direction  An integer that specifies the orientation of the text.
     * @param string  $fontsize   Size of the font (small, medium, large, giant)
     */
    public function text($string, $x, $y, $font = '', $color = 'black', $direction = 0, $fontsize = 'small')
    {
        if (!is_null($this->_imagick)) {
            $fontsize = self::getFontSize($fontsize);

            return $this->_imagick->text($string, $x, $y, $font, $color, $direction, $fontsize);
        } else {
            $string = addslashes('"' . $string . '"');
            $fontsize = self::getFontSize($fontsize);
            $this->_postSrcOperations[] = "-fill $color " . (!empty($font) ? "-font $font" : '') . " -pointsize $fontsize -gravity northwest -draw \"text $x,$y $string\" -fill none";
        }
    }

    /**
     * Draw a circle.
     *
     * @param integer $x     The x coordinate of the centre.
     * @param integer $y     The y coordinate of the centre.
     * @param integer $r     The radius of the circle.
     * @param string $color  The line color of the circle.
     * @param string $fill   The color to fill the circle.
     */
    public function circle($x, $y, $r, $color, $fill = 'none')
    {
        if (!is_null($this->_imagick)) {
            return $this->_imagick->circle($x, $y, $r, $color, $fill);
        } else {
            $xMax = $x + $r;
            $this->_postSrcOperations[] = "-stroke $color -fill $fill -draw \"circle $x,$y $xMax,$y\" -stroke none -fill none";
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
        // TODO: For now, use only convert since ::polygon is called from other
        // methods that are convert-only for now.
        //if (!is_null($this->_imagick)) {
            //return $this->_imagick->polygon($verts, $color, $fill);
        //} else {
            $command = '';
            foreach ($verts as $vert) {
                $command .= sprintf(' %d,%d', $vert['x'], $vert['y']);
            }
            $this->_postSrcOperations[] = "-stroke $color -fill $fill -draw \"polygon $command\" -stroke none -fill none";
        //}
    }

    /**
     * Draw a rectangle.
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
        if (!is_null($this->_imagick)) {
            $this->_imagick->rectangle($x, $y, $width, $height, $color, $fill);
        } else {
            $xMax = $x + $width;
            $yMax = $y + $height;
            $this->_postSrcOperations[] = "-stroke $color -fill $fill -draw \"rectangle $x,$y $xMax,$yMax\" -stroke none -fill none";
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
     * @param string  $color   The line color of the rectangle.
     * @param string  $fill    The color to fill the rounded rectangle with.
     */
    public function roundedRectangle($x, $y, $width, $height, $round, $color, $fill)
    {
        if (!is_null($this->_imagick)) {
            $this->_imagick->roundedRectangle($x, $y, $width, $height, $round, $color, $fill);
        } else {
            $x1 = $x + $width;
            $y1 = $y + $height;
            $this->_postSrcOperations[] = "-stroke $color -fill $fill -draw \"roundRectangle $x,$y $x1,$y1 $round,$round\" -stroke none -fill none";

        }
    }

    /**
     * Draw a line.
     *
     * @param integer $x0     The x coordinate of the start.
     * @param integer $y0     The y coordinate of the start.
     * @param integer $x1     The x coordinate of the end.
     * @param integer $y1     The y coordinate of the end.
     * @param string $color   The line color.
     * @param string $width   The width of the line.
     */
    public function line($x0, $y0, $x1, $y1, $color = 'black', $width = 1)
    {
        if (!is_null($this->_imagick)) {
            return $this->_imagick->line($x0, $y0, $x1, $y1, $color, $width);
        } else {
            $this->_operations[] = "-stroke $color -strokewidth $width -draw \"line $x0,$y0 $x1,$y1\"";
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
        if (!is_null($this->_imagick)) {
            return $this->_imagick->dashedLine($x0, $y0, $x1, $y1, $color,
                                               $width, $dash_length,
                                               $dash_space);
        } else {
            $this->_operations[] = "-stroke $color -strokewidth $width -draw \"line $x0,$y0 $x1,$y1\"";
        }
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
        if (!is_null($this->_imagick)) {
            return $this->_imagick->polyline($verts, $color, $width);
        } else {
            $command = '';
            foreach ($verts as $vert) {
                $command .= sprintf(' %d,%d', $vert['x'], $vert['y']);
            }
            $this->_operations[] = "-stroke $color -strokewidth $width -fill none -draw \"polyline $command\" -strokewidth 1 -stroke none -fill none";
        }
    }

    /**
     * Draw an arc.
     *
     * @param integer $x      The x coordinate of the centre.
     * @param integer $y      The y coordinate of the centre.
     * @param integer $r      The radius of the arc.
     * @param integer $start  The start angle of the arc.
     * @param integer $end    The end angle of the arc.
     * @param string  $color  The line color of the arc.
     * @param string  $fill   The fill color of the arc (defaults to none).
     */
    public function arc($x, $y, $r, $start, $end, $color = 'black', $fill = 'none')
    {
        // Split up arcs greater than 180 degrees into two pieces.
        $this->_postSrcOperations[] = "-stroke $color -fill $fill";
        $mid = round(($start + $end) / 2);
        $x = round($x);
        $y = round($y);
        $r = round($r);
        if ($mid > 90) {
            $this->_postSrcOperations[] = "-draw \"ellipse $x,$y $r,$r $start,$mid\"";
            $this->_postSrcOperations[] = "-draw \"ellipse $x,$y $r,$r $mid,$end\"";
        } else {
            $this->_postSrcOperations[] = "-draw \"ellipse $x,$y $r,$r $start,$end\"";
        }

        // If filled, draw the outline.
        if (!empty($fill)) {
            list($x1, $y1) = $this->_circlePoint($start, $r * 2);
            list($x2, $y2) = $this->_circlePoint($mid, $r * 2);
            list($x3, $y3) = $this->_circlePoint($end, $r * 2);

            // This seems to result in slightly better placement of
            // pie slices.
            $x++;
            $y++;

            $verts = array(array('x' => $x + $x3, 'y' => $y + $y3),
                           array('x' => $x, 'y' => $y),
                           array('x' => $x + $x1, 'y' => $y + $y1));

            if ($mid > 90) {
                $verts1 = array(array('x' => $x + $x2, 'y' => $y + $y2),
                                array('x' => $x, 'y' => $y),
                                array('x' => $x + $x1, 'y' => $y + $y1));
                $verts2 = array(array('x' => $x + $x3, 'y' => $y + $y3),
                                array('x' => $x, 'y' => $y),
                                array('x' => $x + $x2, 'y' => $y + $y2));

                $this->polygon($verts1, $fill, $fill);
                $this->polygon($verts2, $fill, $fill);
            } else {
                $this->polygon($verts, $fill, $fill);
            }

            $this->polyline($verts, $color);

            $this->_postSrcOperations[] = '-stroke none -fill none';
        }
    }

    public function applyEffects()
    {
        $this->raw();
        foreach ($this->_toClean as $tempfile) {
            @unlink($tempfile);
        }
    }

    /**
     * Method to execute a raw command directly in convert. Useful for executing
     * more involved operations that may require multiple convert commands
     * piped into each other for example. Really designed for use by im based
     * Horde_Image_Effect objects..
     *
     * The input and output files are quoted and substituted for __FILEIN__ and
     * __FILEOUT__ respectfully. In order to support piped convert commands, the
     * path to the convert command is substitued for __CONVERT__ (but the
     * initial convert command is added automatically).
     *
     * @param string $cmd    The command string, with substitutable tokens
     * @param array $values  Any values that should be substituted for tokens.
     *
     * @return
     */
    public function executeConvertCmd($cmd, $values = array())
    {
        // First, get a temporary file for the input
        if (strpos($cmd, '__FILEIN__') !== false) {
            $tmpin = $this->toFile($this->_data);
        } else {
            $tmpin = '';
        }

        // Now an output file
        $tmpout = Util::getTempFile('img', false, $this->_tmpdir);

        // Substitue them in the cmd string
        $cmd = str_replace(array('__FILEIN__', '__FILEOUT__', '__CONVERT__'),
                           array('"' . $tmpin . '"', '"' . $tmpout . '"', $this->_convert),
                           $cmd);

        //TODO: See what else needs to be replaced.
        $cmd = $this->_convert . ' ' . $cmd . ' 2>&1';

        // Log it
        $this->_logDebug(sprintf("convert command executed by Horde_Image_im::executeConvertCmd(): %s", $cmd));
        exec($cmd, $output, $retval);
        if ($retval) {
            $this->_logErr(sprintf("Error running command: %s", $cmd . "\n" . implode("\n", $output)));
        }
        $this->_data = file_get_contents($tmpout);

        @unlink($tmpin);
        @unlink($tmpout);
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


    /**
     * Get the version of the convert command available. This needs to be
     * publicly visable since it's used by various Effects.
     *
     * @return A version string suitable for using in version_compare()
     */
    public function getIMVersion()
    {
        static $version = null;
        if (!is_array($version)) {
            if (!is_null($this->_imagick)) {
                $output = $this->_imagick->getVersion();
                $output[0] = $output['versionString'];
            } else {
                $commandline = $this->_convert . ' --version';
                exec($commandline, $output, $retval);
            }
            if (preg_match('/([0-9])\.([0-9])\.([0-9])/', $output[0], $matches)) {
                $version = $matches;
                return $matches;
            } else {
               return false;
            }
        }
        return $version;
    }

    public function addPostSrcOperation($operation)
    {
        $this->_postSrcOperations[] = $operation;
    }

    public function addOperation($operation)
    {
        $this->_operations[] = $operation;
    }

    public function addFileToClean($filename)
    {
        $this->_toClean[] = $filename;
    }

    public function getConvertPath()
    {
        return $this->_convert;
    }

}