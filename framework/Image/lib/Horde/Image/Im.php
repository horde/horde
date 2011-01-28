<?php
/**
 * This class implements the Horde_Image:: API for ImageMagick.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Im extends Horde_Image_Base
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
                                     'canvas',
                                     'multipage',
                                     'pdf');

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
     * Path to the identify binary
     *
     * @string
     */
    protected $_identify;

    /**
     * Cache the number of image pages
     *
     * @var integer
     */
    private $_pages;

    /**
     * Track current page for the iterator
     *
     * @var integer
     */
    private $_currentPage = 0;

    /**
     * Constructor.
     *
     * @see Horde_Image_Base::_construct
     */
    public function __construct($params, $context = array())
    {
        parent::__construct($params, $context);

        if (empty($context['convert'])) {
            throw new InvalidArgumentException('A path to the convert binary is required.');
        }
        $this->_convert = $context['convert'];

        if (!empty($context['identify'])) {
            $this->_identify = $context['identify'];
        }
        if (!empty($params['filename'])) {
            $this->loadFile($params['filename']);
        } elseif (!empty($params['data'])) {
            $this->loadString($params['data']);
        } else {
            $cmd = "-size {$this->_width}x{$this->_height} xc:{$this->_background} +profile \"*\" {$this->_type}:__FILEOUT__";
            $this->executeConvertCmd($cmd);
        }
    }

    /**
     * Publically visible raw method. Hides the extra parameters from client
     * code.
     *
     * @see self::_raw
     */
    public function raw($convert = false)
    {
        return $this->_raw($convert);
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
    private function _raw($convert = false, $index = 0, $preserve_data = false)
    {
        if (empty($this->_data) ||
            // If there are no operations, and we already have data, don't
            // bother writing out files, just return the current data.
            (!$convert &&
             !count($this->_operations) &&
             !count($this->_postSrcOperations))) {
            return $this->_data;
        }

        $tmpin = $this->toFile($this->_data);

        // Perform convert command if needed
        if (count($this->_operations) || count($this->_postSrcOperations) || $convert) {
            $tmpout = Horde_Util::getTempFile('img', false, $this->_tmpdir);
            $command = $this->_convert . ' ' . implode(' ', $this->_operations)
                . ' "' . $tmpin . '"\'[' . $index . ']\' '
                . implode(' ', $this->_postSrcOperations)
                . ' +profile "*" ' . $this->_type . ':"' . $tmpout . '" 2>&1';
            $this->_logDebug(sprintf("convert command executed by Horde_Image_im::raw(): %s", $command));
            exec($command, $output, $retval);
            if ($retval) {
                $error = sprintf("Error running command: %s", $command . "\n" . implode("\n", $output));
                $this->_logErr($error);
                throw new Horde_Image_Exception($error);
            }

            /* Empty the operations queue */
            $this->_operations = array();
            $this->_postSrcOperations = array();

            /* Load the result */
            $return = file_get_contents($tmpout);
            if (!$preserve_data) {
                $this->_data = $return;
            }
        }
        @unlink($tmpin);
        @unlink($tmpout);

        return $return;
    }

    /**
     * Reset the image data.
     */
    public function reset()
    {
        parent::reset();
        $this->_operations = array();
        $this->_postSrcOperations = array();
        $this->clearGeometry();
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
        $resWidth = $width * 2;
        $resHeight = $height * 2;
        $this->_operations[] = "-size {$resWidth}x{$resHeight}";
        if ($ratio) {
            $this->_postSrcOperations[] = (($keepProfile) ? "-resize" : "-thumbnail") . " {$width}x{$height}";
        } else {
            $this->_postSrcOperations[] = (($keepProfile) ? "-resize" : "-thumbnail") . " {$width}x{$height}!";
        }
        // Reset the width and height instance variables since after resize
        // we don't know the *exact* dimensions yet (especially if we maintained
        // aspect ratio.
        // Refresh the data
        $this->raw();
        $this->clearGeometry();
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
        $line = ($x2 - $x1) . 'x' . ($y2 - $y1) . '+' . $x1 . '+' . $y1;
        $this->_operations[] = '-crop ' . $line . ' +repage';

        // Reset width/height since these might change
        $this->raw();
        $this->clearGeometry();
    }

    /**
     * Rotate the current image. This is an atomic operation.
     *
     * @param integer $angle       The angle to rotate the image by,
     *                             in the clockwise direction.
     * @param integer $background  The background color to fill any triangles.
     */
    public function rotate($angle, $background = 'white')
    {
        $this->raw();
        $this->_operations[] = "-background $background -rotate {$angle}";
        $this->raw();

        // Reset width/height since these might have changed
        $this->clearGeometry();
    }

    /**
     * Flip the current image.
     */
    public function flip()
    {
        $this->_operations[] = '-flip';
    }

    /**
     * Mirror the current image.
     */
    public function mirror()
    {
        $this->_operations[] = '-flop';
    }

    /**
     * Convert the current image to grayscale.
     */
    public function grayscale()
    {
        $this->_postSrcOperations[] = '-colorspace GRAY';
    }

    /**
     * Sepia filter.
     *
     * @param integer $threshold  Extent of sepia effect.
     */
    public function sepia($threshold =  85)
    {
        $this->_operations[] = '-sepia-tone ' . $threshold . '%';
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
        $string = addslashes('"' . $string . '"');
        $fontsize = Horde_Image::getFontSize($fontsize);
        $this->_postSrcOperations[] = "-fill $color " . (!empty($font) ? "-font $font" : '') . " -pointsize $fontsize -gravity northwest -draw \"text $x,$y $string\" -fill none";
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
        $xMax = $x + $r;
        $this->_postSrcOperations[] = "-stroke $color -fill $fill -draw \"circle $x,$y $xMax,$y\" -stroke none -fill none";
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
        $command = '';
        foreach ($verts as $vert) {
            $command .= sprintf(' %d,%d', $vert['x'], $vert['y']);
        }
        $this->_postSrcOperations[] = "-stroke $color -fill $fill -draw \"polygon $command\" -stroke none -fill none";
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
        $xMax = $x + $width;
        $yMax = $y + $height;
        $this->_postSrcOperations[] = "-stroke $color -fill $fill -draw \"rectangle $x,$y $xMax,$yMax\" -stroke none -fill none";
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
        $x1 = $x + $width;
        $y1 = $y + $height;
        $this->_postSrcOperations[] = "-stroke $color -fill $fill -draw \"roundRectangle $x,$y $x1,$y1 $round,$round\" -stroke none -fill none";
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
        $this->_operations[] = "-stroke $color -strokewidth $width -draw \"line $x0,$y0 $x1,$y1\"";
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
       $this->_operations[] = "-stroke $color -strokewidth $width -draw \"line $x0,$y0 $x1,$y1\"";
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
        $command = '';
        foreach ($verts as $vert) {
            $command .= sprintf(' %d,%d', $vert['x'], $vert['y']);
        }
        $this->_operations[] = "-stroke $color -strokewidth $width -fill none -draw \"polyline $command\" -strokewidth 1 -stroke none -fill none";
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
            list($x1, $y1) = Horde_Image::circlePoint($start, $r * 2);
            list($x2, $y2) = Horde_Image::circlePoint($mid, $r * 2);
            list($x3, $y3) = Horde_Image::circlePoint($end, $r * 2);

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
        $tmpout = Horde_Util::getTempFile('img', false, $this->_tmpdir);

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
     * Get the version of the convert command available. This needs to be
     * publicly visable since it's used by various Effects.
     *
     * @return A version string suitable for using in version_compare()
     */
    public function getIMVersion()
    {
        static $version = null;
        if (!is_array($version)) {
            $commandline = $this->_convert . ' --version';
            exec($commandline, $output, $retval);
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

    /**
     * Reset the imagick iterator to the first image in the set.
     *
     * @return void
     */
    public function rewind()
    {
        $this->_logDebug('Horde_Image_Im#rewind');
        $this->_currentPage = 0;
    }

    /**
     * Return the current image from the internal iterator.
     *
     * @return Horde_Image_Imagick
     */
    public function current()
    {
        $this->_logDebug('Horde_Image_Im#current');
        return $this->getImageAtIndex($this->_currentPage);
    }

    /**
     * Get the index of the internal iterator.
     *
     * @return integer
     */
    public function key()
    {
        $this->_logDebug('Horde_Image_Im#key');
        return $this->_currentPage;
    }

    /**
     * Advance the iterator
     *
     * @return Horde_Image_Im
     */
    public function next()
    {
        $this->_logDebug('Horde_Image_Im#next');
        $this->_currentPage++;
        if ($this->valid()) {
            return $this->getImageAtIndex($this->_currentPage);
        }
    }

    /**
     * Deterimines if the current iterator item is valid.
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_currentPage < $this->getImagePageCount();
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
        $this->_logDebug('Horde_Image_Im#getImageAtIndex: ' . $index);
        if ($index >= $this->getImagePageCount()) {
            throw new Horde_Image_Exception('Image index out of bounds.');
        }
        $rawImage = $this->_raw(true, $index, true);
        $image = new Horde_Image_Im(array('data' => $rawImage), $this->_context);

        return $image;
    }

    /**
     * Return the number of image pages available in the image object.
     *
     * @return integer
     */
    public function getImagePageCount()
    {
        if (is_null($this->_pages)) {
            $pages = $this->_getImagePages();
            $this->_pages = array_pop($pages);
        }
        $this->_logDebug('Horde_Image_Im#getImagePageCount: ' . $this->_pages);

        return $this->_pages;

    }

    private function _getImagePages()
    {
        $this->_logDebug('Horde_Image_Im#_getImagePages');
        $filename = $this->toFile();
        $cmd = $this->_identify . ' -format "%n" ' . $filename;
        exec($cmd, $output, $retval);
        if ($retval) {
            $this->_logErr(sprintf("Error running command: %s", $cmd . "\n" . implode("\n", $output)));
        }
        unlink($filename);

        return $output;
    }
}
