<?php
/**
 * The Horde_Pdf_Writer class provides a PHP-only implementation of a PDF
 * generation library. No external libs or PHP extensions are required.
 *
 * Based on the FPDF class by Olivier Plathey (http://www.fpdf.org/).
 *
 * Minimal conversion to PHP 5 by Maintainable Software
 * (http://maintainable.com).
 *
 * Copyright 2001-2003 Olivier Plathey <olivier@fpdf.org>
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Olivier Plathey <olivier@fpdf.org>
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://opensource.org/licenses/lgpl-license.php
 * @category Horde
 * @package  Horde_Pdf
 */

/**
 * The Horde_Pdf_Writer class provides a PHP-only implementation of a PDF
 * library. No external libs or PHP extensions are required.
 *
 * @category Horde
 * @package  Horde_Pdf
 */
class Horde_Pdf_Writer
{
    /**
     * Current page number.
     *
     * @var integer
     */
    protected $_page = 0;

    /**
     * Current object number.
     *
     * @var integer
     */
    protected $_n = 2;

    /**
     * Array of object offsets.
     *
     * @var array
     */
    protected $_offsets = array();

    /**
     * Buffer holding in-memory Pdf.
     *
     * @var string
     */
    protected $_buffer = '';

    /**
     * Buffer length, including already flushed content.
     *
     * @var integer
     */
    protected $_buflen = 0;

    /**
     * Whether the buffer has been flushed already.
     *
     * @var boolean
     */
    protected $_flushed = false;

    /**
     * Array containing the pages.
     *
     * @var array
     */
    protected $_pages = array();

    /**
     * Current document state.<pre>
     *   0 - initial state
     *   1 - document opened
     *   2 - page opened
     *   3 - document closed
     * </pre>
     *
     * @var integer
     */
    protected $_state = 0;

    /**
     * Flag indicating if PDF file is to be compressed or not.
     *
     * @var boolean
     */
    protected $_compress;

    /**
     * The default page orientation.
     *
     * @var string
     */
    protected $_default_orientation;

    /**
     * The current page orientation.
     *
     * @var string
     */
    protected $_current_orientation;

    /**
     * Array indicating orientation changes.
     *
     * @var array
     */
    protected $_orientation_changes = array();

    /**
     * Current width of page format in points.
     *
     * @var float
     */
    public $fwPt;

    /**
     * Current height of page format in points.
     *
     * @var float
     */
    public $fhPt;

    /**
     * Current width of page format in user units.
     *
     * @var float
     */
    public $fw;

    /**
     * Current height of page format in user units.
     *
     * @var float
     */
    public $fh;

    /**
     * Current width of page in points.
     *
     * @var float
     */
    public $wPt;

    /**
     * Current height of page in points.
     *
     * @var float
     */
    public $hPt;

    /**
     * Current width of page in user units
     *
     * @var float
     */
    public $w;

    /**
     * Current height of page in user units
     *
     * @var float
     */
    public $h;

    /**
     * Scale factor (number of points in user units).
     *
     * @var float
     */
    protected $_scale;

    /**
     * Left page margin size.
     *
     * @var float
     */
    protected $_left_margin;

    /**
     * Top page margin size.
     *
     * @var float
     */
    protected $_top_margin;

    /**
     * Right page margin size.
     *
     * @var float
     */
    protected $_right_margin;

    /**
     * Break page margin size, the bottom margin which triggers a page break.
     *
     * @var float
     */
    protected $_break_margin;

    /**
     * Cell margin size.
     *
     * @var float
     */
    protected $_cell_margin;

    /**
     * The current horizontal position for cell positioning.
     * Value is set in user units and is calculated from the top left corner
     * as origin.
     *
     * @var float
     */
    public $x;

    /**
     * The current vertical position for cell positioning.
     * Value is set in user units and is calculated from the top left corner
     * as origin.
     *
     * @var float
     */
    public $y;

    /**
     * The height of the last cell printed.
     *
     * @var float
     */
    protected $_last_height;

    /**
     * Line width in user units.
     *
     * @var float
     */
    protected $_line_width;

    /**
     * An array of standard font names.
     *
     * @var array
     */
    protected $_core_fonts = array('courier'      => 'Courier',
                                   'courierB'     => 'Courier-Bold',
                                   'courierI'     => 'Courier-Oblique',
                                   'courierBI'    => 'Courier-BoldOblique',
                                   'helvetica'    => 'Helvetica',
                                   'helveticaB'   => 'Helvetica-Bold',
                                   'helveticaI'   => 'Helvetica-Oblique',
                                   'helveticaBI'  => 'Helvetica-BoldOblique',
                                   'times'        => 'Times-Roman',
                                   'timesB'       => 'Times-Bold',
                                   'timesI'       => 'Times-Italic',
                                   'timesBI'      => 'Times-BoldItalic',
                                   'symbol'       => 'Symbol',
                                   'zapfdingbats' => 'ZapfDingbats');

    /**
     * An array of used fonts.
     *
     * @var array
     */
    protected $_fonts = array();

    /**
     * An array of font files.
     *
     * @var array
     */
    protected $_font_files = array();

    /**
     * Widths of specific font files
     *
     * @var array
     */
    protected static $_font_widths = array();

    /**
     * An array of encoding differences.
     *
     * @var array
     */
    protected $_diffs = array();

    /**
     * An array of used images.
     *
     * @var array
     */
    protected $_images = array();

    /**
     * An array of links in pages.
     *
     * @var array
     */
    protected $_page_links;

    /**
     * An array of internal links.
     *
     * @var array
     */
    protected $_links = array();

    /**
     * Current font family.
     *
     * @var string
     */
    protected $_font_family = '';

    /**
     * Current font style.
     *
     * @var string
     */
    protected $_font_style = '';

    /**
     * Underlining flag.
     *
     * @var boolean
     */
    protected $_underline = false;

    /**
     * An array containing current font info.
     *
     * @var array
     */
    protected $_current_font;

    /**
     * Current font size in points.
     *
     * @var float
     */
    protected $_font_size_pt = 12;

    /**
     * Current font size in user units.
     *
     * @var float
     */
    protected $_font_size = 12;

    /**
     * Commands for filling color.
     *
     * @var string
     */
    protected $_fill_color = '0 g';

    /**
     * Commands for text color.
     *
     * @var string
     */
    protected $_text_color = '0 g';

    /**
     * Whether text color is different from fill color.
     *
     * @var boolean
     */
    protected $_color_flag = false;

    /**
     * Commands for drawing color.
     *
     * @var string
     */
    protected $_draw_color = '0 G';

    /**
     * Word spacing.
     *
     * @var integer
     */
    protected $_word_spacing = 0;

    /**
     * Automatic page breaking.
     *
     * @var boolean
     */
    protected $_auto_page_break;

    /**
     * Threshold used to trigger page breaks.
     *
     * @var float
     */
    protected $_page_break_trigger;

    /**
     * Flag set when processing footer.
     *
     * @var boolean
     */
    protected $_in_footer = false;

    /**
     * Zoom display mode.
     *
     * @var string
     */
    protected $_zoom_mode;

    /**
     * Layout display mode.
     *
     * @var string
     */
    protected $_layout_mode;

    /**
     * An array containing the document info, consisting of:
     *   - title
     *   - subject
     *   - author
     *   - keywords
     *   - creator
     *
     * @var array
     */
    protected $_info = array();

    /**
     * Alias for total number of pages.
     *
     * @var string
     */
    protected $_alias_nb_pages = '{nb}';

    /**
     * Constructor
     *
     * It allows to set up the page format, the orientation and the units of
     * measurement used in all the methods (except for the font sizes).
     *
     * Example:
     * <code>
     * $pdf = new Horde_Pdf_Writer(array('orientation' => 'P',
     *                                   'unit'   => 'mm',
     *                                   'format' => 'A4'));
     * </code>
     *
     * @param array $params  A hash with parameters for the created PDF object.
     *                       Possible parameters are:
     *                       - orientation - Default page orientation. Possible
     *                         values are (case insensitive):
     *                         - P or Portrait (default)
     *                         - L or Landscape
     *                       - unit - User measure units. Possible values
     *                         values are:
     *                         - pt: point
     *                         - mm: millimeter (default)
     *                         - cm: centimeter
     *                         - in: inch
     *                         A point equals 1/72 of inch, that is to say
     *                         about 0.35 mm (an inch being 2.54 cm). This is a
     *                         very common unit in typography; font sizes are
     *                         expressed in that unit.
     *                       - format - The format used for pages. It can be
     *                         either one of the following values (case
     *                         insensitive):
     *                         - A3
     *                         - A4 (default)
     *                         - A5
     *                         - Letter
     *                         - Legal
     *                         or a custom format in the form of a two-element
     *                         array containing the width and the height
     *                         (expressed in the unit given by the unit
     *                         parameter).
     */
    public function __construct($params = array())
    {
        /* Default parameters. */
        $defaults = array('orientation' => 'P', 'unit' => 'mm', 'format' => 'A4');
        $params = array_merge($defaults, $params);

        /* Scale factor. */
        if ($params['unit'] == 'pt') {
            $this->_scale = 1;
        } elseif ($params['unit'] == 'mm') {
            $this->_scale = 72 / 25.4;
        } elseif ($params['unit'] == 'cm') {
            $this->_scale = 72 / 2.54;
        } elseif ($params['unit'] == 'in') {
            $this->_scale = 72;
        } else {
            throw new Horde_Pdf_Exception(sprintf('Incorrect units: %s', $params['unit']));
        }
        /* Page format. */
        if (is_string($params['format'])) {
            $params['format'] = strtolower($params['format']);
            if ($params['format'] == 'a3') {
                $params['format'] = array(841.89, 1190.55);
            } elseif ($params['format'] == 'a4') {
                $params['format'] = array(595.28, 841.89);
            } elseif ($params['format'] == 'a5') {
                $params['format'] = array(420.94, 595.28);
            } elseif ($params['format'] == 'letter') {
                $params['format'] = array(612, 792);
            } elseif ($params['format'] == 'legal') {
                $params['format'] = array(612, 1008);
            } else {
                throw new Horde_Pdf_Exception(sprintf('Unknown page format: %s', $params['format']));
            }
            $this->fwPt = $params['format'][0];
            $this->fhPt = $params['format'][1];
        } else {
            $this->fwPt = $params['format'][0] * $this->_scale;
            $this->fhPt = $params['format'][1] * $this->_scale;
        }
        $this->fw = $this->fwPt / $this->_scale;
        $this->fh = $this->fhPt / $this->_scale;

        /* Page orientation. */
        $params['orientation'] = strtolower($params['orientation']);
        if ($params['orientation'] == 'p' || $params['orientation'] == 'portrait') {
            $this->_default_orientation = 'P';
            $this->wPt = $this->fwPt;
            $this->hPt = $this->fhPt;
        } elseif ($params['orientation'] == 'l' || $params['orientation'] == 'landscape') {
            $this->_default_orientation = 'L';
            $this->wPt = $this->fhPt;
            $this->hPt = $this->fwPt;
        } else {
            throw new Horde_Pdf_Exception(sprintf('Incorrect orientation: %s', $params['orientation']));
        }
        $this->_current_orientation = $this->_default_orientation;
        $this->w = $this->wPt / $this->_scale;
        $this->h = $this->hPt / $this->_scale;

        /* Page margins (1 cm) */
        $margin = 28.35 / $this->_scale;
        $this->setMargins($margin, $margin);

        /* Interior cell margin (1 mm) */
        $this->_cell_margin = $margin / 10;

        /* Line width (0.2 mm) */
        $this->_line_width = .567 / $this->_scale;

        /* Automatic page break */
        $this->setAutoPageBreak(true, 2 * $margin);

        /* Full width display mode */
        $this->setDisplayMode('fullwidth');

        /* Compression */
        $this->setCompression(true);
    }

    /**
     * Defines the left, top and right margins.
     *
     * By default, they equal 1 cm. Call this method to change them.
     *
     * @param float $left   Left margin.
     * @param float $top    Top margin.
     * @param float $right  Right margin. If not specified default to the value
     *                      of the left one.
     *
     * @see setAutoPageBreak()
     * @see setLeftMargin()
     * @see setRightMargin()
     * @see setTopMargin()
     */
    public function setMargins($left, $top, $right = null)
    {
        /* Set left and top margins. */
        $this->_left_margin  = $left;
        $this->_top_margin   = $top;
        /* If no right margin set default to same as left. */
        $this->_right_margin = (is_null($right) ? $left : $right);
    }

    /**
     * Defines the left margin.
     *
     * The method can be called before creating the first page.  If the
     * current abscissa gets out of page, it is brought back to the margin.
     *
     * @param float $margin  The margin.
     *
     * @see setAutoPageBreak()
     * @see setMargins()
     * @see setRightMargin()
     * @see setTopMargin()
     */
    public function setLeftMargin($margin)
    {
        $this->_left_margin = $margin;
        /* If there is a current page and the current X position is less than
         * margin set the X position to the margin value. */
        if ($this->_page > 0 && $this->x < $margin) {
            $this->x = $margin;
        }
    }

    /**
     * Defines the top margin.
     *
     * The method can be called before creating the first page.
     *
     * @param float $margin  The margin.
     */
    public function setTopMargin($margin)
    {
        $this->_top_margin = $margin;
    }

    /**
     * Defines the right margin.
     *
     * The method can be called before creating the first page.
     *
     * @param float $margin  The margin.
     */
    public function setRightMargin($margin)
    {
        $this->_right_margin = $margin;
    }

    /**
     * Returns the actual page width.
     *
     * @return float  The page width.
     */
    public function getPageWidth()
    {
        return ($this->w - $this->_right_margin - $this->_left_margin);
    }

    /**
     * Returns the actual page height.
     *
     * @return float  The page height.
     */
    public function getPageHeight()
    {
        return ($this->h - $this->_top_margin - $this->_break_margin);
    }

    /**
     * Enables or disables the automatic page breaking mode.
     *
     * When enabling, the second parameter is the distance from the bottom of
     * the page that defines the triggering limit. By default, the mode is on
     * and the margin is 2 cm.
     *
     * @param boolean $auto  Boolean indicating if mode should be on or off.
     * @param float $margin  Distance from the bottom of the page.
     */
    public function setAutoPageBreak($auto, $margin = 0)
    {
        $this->_auto_page_break    = $auto;
        $this->_break_margin       = $margin;
        $this->_page_break_trigger = $this->h - $margin;
    }

    /**
     * Defines the way the document is to be displayed by the viewer.
     *
     * The zoom level can be set: pages can be displayed entirely on screen,
     * occupy the full width of the window, use real size, be scaled by a
     * specific zooming factor or use viewer default (configured in the
     * Preferences menu of Acrobat). The page layout can be specified too:
     * single at once, continuous display, two columns or viewer default.  By
     * default, documents use the full width mode with continuous display.
     *
     * @param mixed $zoom    The zoom to use. It can be one of the following
     *                       string values:
     *                         - fullpage: entire page on screen
     *                         - fullwidth: maximum width of window
     *                         - real: uses real size (100% zoom)
     *                         - default: uses viewer default mode
     *                       or a number indicating the zooming factor.
     * @param string layout  The page layout. Possible values are:
     *                         - single: one page at once
     *                         - continuous: pages in continuously
     *                         - two: two pages on two columns
     *                         - default: uses viewer default mode
     *                       Default value is continuous.
     */
    public function setDisplayMode($zoom, $layout = 'continuous')
    {
        $zoom = strtolower($zoom);
        if ($zoom == 'fullpage' || $zoom == 'fullwidth' || $zoom == 'real'
            || $zoom == 'default' || !is_string($zoom)) {
            $this->_zoom_mode = $zoom;
        } elseif ($zoom == 'zoom') {
            $this->_zoom_mode = $layout;
        } else {
            throw new Horde_Pdf_Exception(sprintf('Incorrect zoom display mode: %s', $zoom));
        }

        $layout = strtolower($layout);
        if ($layout == 'single' || $layout == 'continuous' || $layout == 'two'
            || $layout == 'default') {
            $this->_layout_mode = $layout;
        } elseif ($zoom != 'zoom') {
            throw new Horde_Pdf_Exception(sprintf('Incorrect layout display mode: %s', $layout));
        }
    }

    /**
     * Activates or deactivates page compression.
     *
     * When activated, the internal representation of each page is compressed,
     * which leads to a compression ratio of about 2 for the resulting
     * document. Compression is on by default.
     *
     * Note: the {@link http://www.php.net/zlib/ zlib extension} is required
     * for this feature. If not present, compression will be turned off.
     *
     * @param boolean $compress  Boolean indicating if compression must be
     *                           enabled or not.
     */
    public function setCompression($compress)
    {
        /* If no gzcompress function is available then default to false. */
        $this->_compress = (function_exists('gzcompress') ? $compress : false);
    }

    /**
     * Set the info to a document.
     *
     * Possible info settings are:
     *   - title
     *   - subject
     *   - author
     *   - keywords
     *   - creator
     *
     * @param array|string $info  If passed as an array then the complete hash
     *                            containing the info to be inserted into the
     *                            document. Otherwise the name of setting to be
     *                            set.
     * @param string $value       The value of the setting.
     */
    public function setInfo($info, $value = '')
    {
        if (is_array($info)) {
            $this->_info = $info;
        } else {
            $this->_info[$info] = $value;
        }
    }

    /**
     * Defines an alias for the total number of pages.
     *
     * It will be substituted as the document is closed.
     *
     * Example:
     * <code>
     * class My_Pdf extends Horde_Pdf_Writer {
     *     function footer()
     *     {
     *         // Go to 1.5 cm from bottom
     *         $this->setY(-15);
     *         // Select Arial italic 8
     *         $this->setFont('Arial', 'I', 8);
     *         // Print current and total page numbers
     *         $this->cell(0, 10, 'Page ' . $this->getPageNo() . '/{nb}', 0,
     *                     0, 'C');
     *     }
     * }
     * $pdf = new My_Pdf();
     * $pdf->aliasNbPages();
     * </code>
     *
     * @param string $alias  The alias.
     *
     * @see getPageNo()
     * @see footer()
     */
    public function aliasNbPages($alias = '{nb}')
    {
        $this->_alias_nb_pages = $alias;
    }

    /**
     * This method begins the generation of the PDF document; it must be
     * called before any output commands.
     *
     * No page is created by this method, therefore it is necessary to call
     * {@link addPage()}.
     *
     * @see addPage()
     * @see close()
     */
    public function open()
    {
        $this->_beginDoc();
    }

    /**
     * Terminates the PDF document.
     *
     * If the document contains no page, {@link addPage()} is called to
     * prevent from getting an invalid document.
     *
     * @see open()
     */
    public function close()
    {
        // Terminate document
        if ($this->_page == 0) {
            $this->addPage();
        }

        // Page footer
        $this->_in_footer = true;
        $this->x = $this->_left_margin;
        $this->footer();
        $this->_in_footer = false;

        // Close page and document
        $this->_endPage();
        $this->_endDoc();
    }

    /**
     * Adds a new page to the document.
     *
     * If a page is already present, the {@link footer()} method is called
     * first to output the footer. Then the page is added, the current
     * position set to the top-left corner according to the left and top
     * margins, and {@link header()} is called to display the header.
     *
     * The font which was set before calling is automatically restored. There
     * is no need to call {@link setFont()} again if you want to continue with
     * the same font. The same is true for colors and line width.  The origin
     * of the coordinate system is at the top-left corner and increasing
     * ordinates go downwards.
     *
     * @param string $orientation  Page orientation. Possible values
     *                             are (case insensitive):
     *                               - P or Portrait
     *                               - L or Landscape
     *                             The default value is the one passed to the
     *                             constructor.
     *
     * @see header()
     * @see footer()
     * @see setMargins()
     */
    public function addPage($orientation = '')
    {
        /* For good measure make sure this is called. */
        $this->_beginDoc();

        /* Save style settings so that they are not overridden by
         * footer() or header(). */
        $lw = $this->_line_width;
        $dc = $this->_draw_color;
        $fc = $this->_fill_color;
        $tc = $this->_text_color;
        $cf = $this->_color_flag;
        $font_family = $this->_font_family;
        $font_style  = $this->_font_style . ($this->_underline ? 'U' : '');
        $font_size   = $this->_font_size_pt;

        if ($this->_page > 0) {
            /* Page footer. */
            $this->_in_footer = true;
            $this->x = $this->_left_margin;
            $this->footer();
            $this->_in_footer = false;

            /* Close page. */
            $this->_endPage();
        }

        /* Start new page. */
        $this->_beginPage($orientation);

        /* Set line cap style to square. */
        $this->_out('2 J');

        /* Set line width. */
        $this->_line_width = $lw;
        $this->_out(sprintf('%.2F w', $lw * $this->_scale));

        /* Force the setting of the font. Each new page requires a new
         * call. */
        if ($font_family) {
            $this->setFont($font_family, $font_style, $font_size, true);
        }

        /* Restore styles. */
        if ($this->_fill_color != $fc) {
            $this->_fill_color = $fc;
            $this->_out($this->_fill_color);
        }
        if ($this->_draw_color != $dc) {
            $this->_draw_color = $dc;
            $this->_out($this->_draw_color);
        }
        $this->_text_color = $tc;
        $this->_color_flag = $cf;

        /* Page header. */
        $this->header();

        /* Restore styles. */
        if ($this->_line_width != $lw) {
            $this->_line_width = $lw;
            $this->_out(sprintf('%.2F w', $lw * $this->_scale));
        }
        $this->setFont($font_family, $font_style, $font_size);
        if ($this->_fill_color != $fc) {
            $this->_fill_color = $fc;
            $this->_out($this->_fill_color);
        }
        if ($this->_draw_color != $dc) {
            $this->_draw_color = $dc;
            $this->_out($this->_draw_color);
        }
        $this->_text_color = $tc;
        $this->_color_flag = $cf;
    }

    /**
     * This method is used to render the page header.
     *
     * It is automatically called by {@link addPage()} and should not be
     * called directly by the application. The implementation in Horde_Pdf_Writer is
     * empty, so you have to subclass it and override the method if you want a
     * specific processing.
     *
     * Example:
     * <code>
     * class My_Pdf extends Horde_Pdf_Writer {
     *     function header()
     *     {
     *         // Select Arial bold 15
     *         $this->setFont('Arial', 'B', 15);
     *         // Move to the right
     *         $this->cell(80);
     *         // Framed title
     *         $this->cell(30, 10, 'Title', 1, 0, 'C');
     *         // Line break
     *         $this->newLine(20);
     *     }
     * }
     * </code>
     *
     * @see footer()
     */
    public function header()
    {
        /* To be implemented in your own inherited class. */
    }

    /**
     * This method is used to render the page footer.
     *
     * It is automatically called by {@link addPage()} and {@link close()} and
     * should not be called directly by the application. The implementation in
     * Horde_Pdf_Writer is empty, so you have to subclass it and override the method
     * if you want a specific processing.
     *
     * Example:
     * <code>
     * class My_Pdf extends Horde_Pdf_Writer {
     *    function footer()
     *    {
     *        // Go to 1.5 cm from bottom
     *        $this->setY(-15);
     *        // Select Arial italic 8
     *        $this->setFont('Arial', 'I', 8);
     *        // Print centered page number
     *        $this->cell(0, 10, 'Page ' . $this->getPageNo(), 0, 0, 'C');
     *    }
     * }
     * </code>
     *
     * @see header()
     */
    public function footer()
    {
        /* To be implemented in your own inherited class. */
    }

    /**
     * Returns the current page number.
     *
     * @return integer
     *
     * @see aliasNbPages()
     */
    public function getPageNo()
    {
        return $this->_page;
    }

    /**
     * Sets the fill color.
     *
     * Depending on the colorspace called, the number of color component
     * parameters required can be either 1, 3 or 4. The method can be called
     * before the first page is created and the color is retained from page to
     * page.
     *
     * @param string $cs  Indicates the colorspace which can be either 'rgb',
     *                    'hex', 'cmyk', or 'gray'. Defaults to 'rgb'.
     * @param float $c1   First color component, floating point value between 0
     *                    and 1. Required for gray, rgb and cmyk.
     * @param float $c2   Second color component, floating point value
     *                    between 0 and 1. Required for rgb and cmyk.
     * @param float $c3   Third color component, floating point value between 0
     *                    and 1. Required for rgb and cmyk.
     * @param float $c4   Fourth color component, floating point value
     *                    between 0 and 1. Required for cmyk.
     *
     * @see setTextColor()
     * @see setDrawColor()
     * @see rect()
     * @see cell()
     * @see multiCell()
     */
    public function setFillColor($cs = 'rgb', $c1, $c2 = 0, $c3 = 0, $c4 = 0)
    {
        $cs = strtolower($cs);

        // convert hex to rgb
        if ($cs == 'hex') {
            $cs = 'rgb';
            list($c1, $c2, $c3) = $this->_hexToRgb($c1);
        }

        if ($cs == 'rgb') {
            $this->_fill_color = sprintf('%.3F %.3F %.3F rg', $c1, $c2, $c3);
        } elseif ($cs == 'cmyk') {
            $this->_fill_color = sprintf('%.3F %.3F %.3F %.3F k', $c1, $c2, $c3, $c4);
        } else {
            $this->_fill_color = sprintf('%.3F g', $c1);
        }
        if ($this->_page > 0) {
            $this->_out($this->_fill_color);
        }
        $this->_color_flag = $this->_fill_color != $this->_text_color;
    }

    /**
     * Get the fill color
     *
     * @return  string
     */
    public function getFillColor()
    {
        return $this->_fill_color;
    }

    /**
     * Sets the text color.
     *
     * Depending on the colorspace called, the number of color component
     * parameters required can be either 1, 3 or 4. The method can be called
     * before the first page is created and the color is retained from page to
     * page.
     *
     * @param string $cs  Indicates the colorspace which can be either 'rgb',
     *                    'hex', 'cmyk' or 'gray'. Defaults to 'rgb'.
     * @param float $c1   First color component, floating point value between 0
     *                    and 1. Required for gray, rgb and cmyk.
     * @param float $c2   Second color component, floating point value
     *                    between 0 and 1. Required for rgb and cmyk.
     * @param float $c3   Third color component, floating point value between 0
     *                    and 1. Required for rgb and cmyk.
     * @param float $c4   Fourth color component, floating point value
     *                    between 0 and 1. Required for cmyk.
     *
     * @see setFillColor()
     * @see setDrawColor()
     * @see rect()
     * @see cell()
     * @see multiCell()
     */
    public function setTextColor($cs = 'rgb', $c1, $c2 = 0, $c3 = 0, $c4 = 0)
    {
        $cs = strtolower($cs);

        // convert hex to rgb
        if ($cs == 'hex') {
            $cs = 'rgb';
            list($c1, $c2, $c3) = $this->_hexToRgb($c1);
        }

        if ($cs == 'rgb') {
            $this->_text_color = sprintf('%.3F %.3F %.3F rg', $c1, $c2, $c3);
        } elseif ($cs == 'cmyk') {
            $this->_text_color = sprintf('%.3F %.3F %.3F %.3F k', $c1, $c2, $c3, $c4);
        } else {
            $this->_text_color = sprintf('%.3F g', $c1);
        }

        $this->_color_flag = $this->_fill_color != $this->_text_color;
    }

    /**
     * Get the text color
     *
     * @return  string
     */
    public function getTextColor()
    {
        return $this->_text_color;
    }

    /**
     * Sets the draw color, used when drawing lines.
     *
     * Depending on the colorspace called, the number of color component
     * parameters required can be either 1, 3 or 4. The method can be called
     * before the first page is created and the color is retained from page to
     * page.
     *
     * @param string $cs  Indicates the colorspace which can be either 'rgb',
     *                    'hex', 'cmyk' or 'gray'. Defaults to 'rgb'.
     * @param float $c1   First color component, floating point value between 0
     *                    and 1. Required for gray, rgb and cmyk.
     * @param float $c2   Second color component, floating point value
     *                    between 0 and 1. Required for rgb and cmyk.
     * @param float $c3   Third color component, floating point value between 0
     *                    and 1. Required for rgb and cmyk.
     * @param float $c4   Fourth color component, floating point value
     *                    between 0 and 1. Required for cmyk.
     *
     * @see setFillColor()
     * @see line()
     * @see rect()
     * @see cell()
     * @see multiCell()
     */
    public function setDrawColor($cs = 'rgb', $c1, $c2 = 0, $c3 = 0, $c4 = 0)
    {
        $cs = strtolower($cs);

        // convert hex to rgb
        if ($cs == 'hex') {
            $cs = 'rgb';
            list($c1, $c2, $c3) = $this->_hexToRgb($c1);
        }

        if ($cs == 'rgb') {
            $this->_draw_color = sprintf('%.3F %.3F %.3F RG', $c1, $c2, $c3);
        } elseif ($cs == 'cmyk') {
            $this->_draw_color = sprintf('%.3F %.3F %.3F %.3F K', $c1, $c2, $c3, $c4);
        } else {
            $this->_draw_color = sprintf('%.3F G', $c1);
        }
        if ($this->_page > 0) {
            $this->_out($this->_draw_color);
        }
    }

    /**
     * Get the draw color
     *
     * @return  string
     */
    public function getDrawColor()
    {
        return $this->_draw_color;
    }

    /**
     * Returns the length of a text string. A font must be selected.
     *
     * @param string $text  The text whose length is to be computed.
     * @param boolean $pt   Whether the width should be returned in points or
     *                      user units.
     *
     * @return float
     */
    public function getStringWidth($text, $pt = false)
    {
        $text = (string)$text;
        $width = 0;
        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $width += $this->_current_font['cw'][$text{$i}];
        }

        /* Adjust for word spacing. */
        $width += $this->_word_spacing * substr_count($text, ' ') * $this->_current_font['cw'][' '];

        if ($pt) {
            return $width * $this->_font_size_pt / 1000;
        } else {
            return $width * $this->_font_size / 1000;
        }
    }

    /**
     * Defines the line width.
     *
     * By default, the value equals 0.2 mm. The method can be called before
     * the first page is created and the value is retained from page to page.
     *
     * @param float $width  The width.
     *
     * @see line()
     * @see rect()
     * @see cell()
     * @see multiCell()
     */
    public function setLineWidth($width)
    {
        $this->_line_width = $width;
        if ($this->_page > 0) {
            $this->_out(sprintf('%.2F w', $width * $this->_scale));
        }
    }

    /**
     * P (portrait) or L (landscape)
     *
     * @return  string
     */
    public function getDefaultOrientation()
    {
        return $this->_default_orientation;
    }

    /**
     * @return  integer
     */
    public function getScale()
    {
        return $this->_scale;
    }

    /**
     * @return  float
     */
    public function getFormatHeight()
    {
        return $this->fwPt;
    }

    /**
     * @return  float
     */
    public function getFormatWidth()
    {
        return $this->fhPt;
    }

    /**
     * Draws a line between two points.
     *
     * All coordinates can be negative to provide values from the right or
     * bottom edge of the page (since File_Pdf 0.2.0, Horde 3.2).
     *
     * @param float $x1  Abscissa of first point.
     * @param float $y1  Ordinate of first point.
     * @param float $x2  Abscissa of second point.
     * @param float $y2  Ordinate of second point.
     *
     * @see setLineWidth()
     * @see setDrawColor()
     */
    public function line($x1, $y1, $x2, $y2)
    {
        if ($x1 < 0) {
            $x1 += $this->w;
        }
        if ($y1 < 0) {
            $y1 += $this->h;
        }
        if ($x2 < 0) {
            $x2 += $this->w;
        }
        if ($y2 < 0) {
            $y2 += $this->h;
        }

        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1 * $this->_scale, ($this->h - $y1) * $this->_scale, $x2 * $this->_scale, ($this->h - $y2) * $this->_scale));
    }

    /**
     * Outputs a rectangle.
     *
     * It can be drawn (border only), filled (with no border) or both.
     *
     * All coordinates can be negative to provide values from the right or
     * bottom edge of the page (since File_Pdf 0.2.0, Horde 3.2).
     *
     * @param float $x       Abscissa of upper-left corner.
     * @param float $y       Ordinate of upper-left corner.
     * @param float $width   Width.
     * @param float $height  Height.
     * @param float $style   Style of rendering. Possible values are:
     *                         - D or empty string: draw (default)
     *                         - F: fill
     *                         - DF or FD: draw and fill
     *
     * @see setLineWidth()
     * @see setDrawColor()
     * @see setFillColor()
     */
    public function rect($x, $y, $width, $height, $style = '')
    {
        if ($x < 0) {
            $x += $this->w;
        }
        if ($y < 0) {
            $y += $this->h;
        }

        $style = strtoupper($style);
        if ($style == 'F') {
            $op = 'f';
        } elseif ($style == 'FD' || $style == 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }

        $x      = $this->_toPt($x);
        $y      = $this->_toPt($y);
        $width  = $this->_toPt($width);
        $height = $this->_toPt($height);

        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x, $this->hPt - $y, $width, -$height, $op));
    }

    /**
     * Outputs a circle. It can be drawn (border only), filled (with no
     * border) or both.
     *
     * All coordinates can be negative to provide values from the right or
     * bottom edge of the page (since File_Pdf 0.2.0, Horde 3.2).
     *
     * @param float $x       Abscissa of the center of the circle.
     * @param float $y       Ordinate of the center of the circle.
     * @param float $r       Circle radius.
     * @param string $style  Style of rendering. Possible values are:
     *                         - D or empty string: draw (default)
     *                         - F: fill
     *                         - DF or FD: draw and fill
     */
    public function circle($x, $y, $r, $style = '')
    {
        if ($x < 0) {
            $x += $this->w;
        }
        if ($y < 0) {
            $y += $this->h;
        }

        $style = strtolower($style);
        if ($style == 'f') {
            $op = 'f';      // Style is fill only.
        } elseif ($style == 'fd' || $style == 'df') {
            $op = 'B';      // Style is fill and stroke.
        } else {
            $op = 'S';      // Style is stroke only.
        }

        $x = $this->_toPt($x);
        $y = $this->_toPt($y);
        $r = $this->_toPt($r);

        /* Invert the y scale. */
        $y = $this->hPt - $y;
        /* Length of the Bezier control. */
        $b = $r * 0.552;

        /* Move from the given origin and set the current point
         * to the start of the first Bezier curve. */
        $c = sprintf('%.2F %.2F m', $x - $r, $y);
        $x = $x - $r;
        /* First circle quarter. */
        $c .= sprintf(' %.2F %.2F %.2F %.2F %.2F %.2F c',
                      $x, $y + $b,           // First control point.
                      $x + $r - $b, $y + $r, // Second control point.
                      $x + $r, $y + $r);     // Final point.
        /* Set x/y to the final point. */
        $x = $x + $r;
        $y = $y + $r;
        /* Second circle quarter. */
        $c .= sprintf(' %.2F %.2F %.2F %.2F %.2F %.2F c',
                      $x + $b, $y,
                      $x + $r, $y - $r + $b,
                      $x + $r, $y - $r);
        /* Set x/y to the final point. */
        $x = $x + $r;
        $y = $y - $r;
        /* Third circle quarter. */
        $c .= sprintf(' %.2F %.2F %.2F %.2F %.2F %.2F c',
                      $x, $y - $b,
                      $x - $r + $b, $y - $r,
                      $x - $r, $y - $r);
        /* Set x/y to the final point. */
        $x = $x - $r;
        $y = $y - $r;
        /* Fourth circle quarter. */
        $c .= sprintf(' %.2F %.2F %.2F %.2F %.2F %.2F c %s',
                      $x - $b, $y,
                      $x - $r, $y + $r - $b,
                      $x - $r, $y + $r,
                      $op);
        /* Output the whole string. */
        $this->_out($c);
    }

    /**
     * Imports a TrueType or Type1 font and makes it available. It is
     * necessary to generate a font definition file first with the
     * makefont.php utility.
     * The location of the definition file (and the font file itself when
     * embedding) must be found at the full path name included.
     *
     * Example:
     * <code>
     * $pdf->addFont('Comic', 'I');
     * is equivalent to:
     * $pdf->addFont('Comic', 'I', 'comici.php');
     * </code>
     *
     * @param string $family  Font family. The name can be chosen arbitrarily.
     *                        If it is a standard family name, it will
     *                        override the corresponding font.
     * @param string $style   Font style. Possible values are (case
     *                        insensitive):
     *                          - empty string: regular (default)
     *                          - B: bold
     *                          - I: italic
     *                          - BI or IB: bold italic
     * @param string $file    The font definition file. By default, the name is
     *                        built from the family and style, in lower case
     *                        with no space.
     *
     * @see setFont()
     * @todo Fonts use a class instead of a definition file
     */
    public function addFont($family, $style = '', $file = '')
    {
        $family = strtolower($family);
        if ($family == 'arial') {
            $family = 'helvetica';
        }

        $style = strtoupper($style);
        if ($style == 'IB') {
            $style = 'BI';
        }
        if (isset($this->_fonts[$family . $style])) {
            throw new Horde_Pdf_Exception(sprintf('Font already added: %s %s', $family, $style));
        }
        if ($file == '') {
            $file = str_replace(' ', '', $family) . strtolower($style) . '.php';
        }
        include($file);
        if (!isset($name)) {
            throw new Horde_Pdf_Exception('Could not include font definition file');
        }
        $i = count($this->_fonts) + 1;
        $this->_fonts[$family . $style] = array('i' => $i, 'type' => $type, 'name' => $name, 'desc' => $desc, 'up' => $up, 'ut' => $ut, 'cw' => $cw, 'enc' => $enc, 'file' => $file);
        if ($diff) {
            /* Search existing encodings. */
            $d = 0;
            $nb = count($this->_diffs);
            for ($i = 1; $i <= $nb; $i++) {
                if ($this->_diffs[$i] == $diff) {
                    $d = $i;
                    break;
                }
            }
            if ($d == 0) {
                $d = $nb + 1;
                $this->_diffs[$d] = $diff;
            }
            $this->_fonts[$family . $style]['diff'] = $d;
        }
        if ($file) {
            if ($type == 'TrueType') {
                $this->_font_files[$file] = array('length1' => $originalsize);
            } else {
                $this->_font_files[$file] = array('length1' => $size1, 'length2' => $size2);
            }
        }
    }

    /**
     * Sets the font used to print character strings.
     *
     * It is mandatory to call this method at least once before printing text
     * or the resulting document would not be valid. The font can be either a
     * standard one or a font added via the {@link addFont()} method. Standard
     * fonts use Windows encoding cp1252 (Western Europe).
     *
     * The method can be called before the first page is created and the font
     * is retained from page to page.
     *
     * If you just wish to change the current font size, it is simpler to call
     * {@link setFontSize()}.
     *
     * @param string $family  Family font. It can be either a name defined by
     *                        {@link addFont()} or one of the standard families
     *                        (case insensitive):
     *                          - Courier (fixed-width)
     *                          - Helvetica or Arial (sans serif)
     *                          - Times (serif)
     *                          - Symbol (symbolic)
     *                          - ZapfDingbats (symbolic)
     *                        It is also possible to pass an empty string. In
     *                        that case, the current family is retained.
     * @param string $style   Font style. Possible values are (case
     *                        insensitive):
     *                          - empty string: regular
     *                          - B: bold
     *                          - I: italic
     *                          - U: underline
     *                        or any combination. Bold and italic styles do not
     *                        apply to Symbol and ZapfDingbats.
     * @param integer $size   Font size in points. The default value is the
     *                        current size. If no size has been specified since
     *                        the beginning of the document, the value taken
     *                        is 12.
     * @param boolean $force  Force the setting of the font. Each new page will
     *                        require a new call to {@link setFont()} and
     *                        setting this to true will make sure that the
     *                        checks for same font calls will be skipped.
     *
     * @see addFont()
     * @see setFontSize()
     * @see cell()
     * @see multiCell()
     * @see write()
     */
    public function setFont($family, $style = '', $size = null, $force = false)
    {
        $family = strtolower($family);
        if (empty($family)) {
            $family = $this->_font_family;
        }
        if ($family == 'arial') {
            /* Use helvetica instead of arial. */
            $family = 'helvetica';
        } elseif ($family == 'symbol' || $family == 'zapfdingbats') {
            /* These two fonts do not have styles available. */
            $style = '';
        }

        $style = strtoupper($style);

        /* Underline is handled separately, if specified in the style var
         * remove it from the style and set the underline flag. */
        if (strpos($style, 'U') !== false) {
            $this->_underline = true;
            $style = str_replace('U', '', $style);
        } else {
            $this->_underline = false;
        }

        if ($style == 'IB') {
            $style = 'BI';
        }

        /* If no size specified, use current size. */
        if (is_null($size)) {
            $size = $this->_font_size_pt;
        }

        /* If font requested is already the current font and no force setting
         * of the font is requested (eg. when adding a new page) don't bother
         * with the rest of the function and simply return. */
        if ($this->_font_family == $family && $this->_font_style == $style &&
            $this->_font_size_pt == $size && !$force) {
            return;
        }

        /* Set the font key. */
        $fontkey = $family . $style;

        /* Test if already cached. */
        if (!isset($this->_fonts[$fontkey])) {
            /* Get the character width definition file. */
            $font_widths = self::_getFontFile($fontkey);

            $i = count($this->_fonts) + 1;
            $this->_fonts[$fontkey] = array(
                'i'    => $i,
                'type' => 'core',
                'name' => $this->_core_fonts[$fontkey],
                'up'   => -100,
                'ut'   => 50,
                'cw'   => $font_widths[$fontkey]);
        }

        /* Store font information as current font. */
        $this->_font_family  = $family;
        $this->_font_style   = $style;
        $this->_font_size_pt = $size;
        $this->_font_size    = $size / $this->_scale;
        $this->_current_font = $this->_fonts[$fontkey];

        /* Output font information if at least one page has been defined. */
        if ($this->_page > 0) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->_current_font['i'], $this->_font_size_pt));
        }
    }

    /**
     * Defines the size of the current font.
     *
     * @param float $size  The size (in points).
     *
     * @see setFont()
     */
    public function setFontSize($size)
    {
        /* If the font size is already the current font size, just return. */
        if ($this->_font_size_pt == $size) {
            return;
        }
        /* Set the current font size, both in points and scaled to user
         * units. */
        $this->_font_size_pt = $size;
        $this->_font_size = $size / $this->_scale;

        /* Output font information if at least one page has been defined. */
        if ($this->_page > 0) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->_current_font['i'], $this->_font_size_pt));
        }
    }

    /**
     * Defines the style of the current font.
     *
     * @param string $style  The font style.
     *
     * @see setFont()
     */
    public function setFontStyle($style)
    {
        $this->setFont($this->_font_family, $style);
    }

    /**
     * Creates a new internal link and returns its identifier.
     *
     * An internal link is a clickable area which directs to another place
     * within the document.
     *
     * The identifier can then be passed to {@link cell()}, {@link()} write,
     * {@link image()} or {@link link()}. The destination is defined with
     * {@link setLink()}.
     *
     * @see cell()
     * @see write()
     * @see image()
     * @see link()
     * @see setLink()
     */
    public function addLink()
    {
        $n = count($this->_links) + 1;
        $this->_links[$n] = array(0, 0);
        return $n;
    }

    /**
     * Defines the page and position a link points to.
     *
     * @param integer $link  The link identifier returned by {@link addLink()}.
     * @param float $y       Ordinate of target position; -1 indicates the
     *                       current position. The default value is 0 (top of
     *                       page).
     * @param integer $page  Number of target page; -1 indicates the current
     *                       page.
     *
     * @see addLink()
     */
    public function setLink($link, $y = 0, $page = -1)
    {
        if ($y == -1) {
            $y = $this->y;
        }
        if ($page == -1) {
            $page = $this->_page;
        }
        $this->_links[$link] = array($page, $y);
    }

    /**
     * Puts a link on a rectangular area of the page.
     *
     * Text or image links are generally put via {@link cell()}, {@link
     * write()} or {@link image()}, but this method can be useful for instance
     * to define a clickable area inside an image.
     *
     * All coordinates can be negative to provide values from the right or
     * bottom edge of the page (since File_Pdf 0.2.0, Horde 3.2).
     *
     * @param float $x       Abscissa of the upper-left corner of the
     *                       rectangle.
     * @param float $y       Ordinate of the upper-left corner of the
     *                       rectangle.
     * @param float $width   Width of the rectangle.
     * @param float $height  Height of the rectangle.
     * @param mixed $link    URL or identifier returned by {@link addLink()}.
     *
     * @see addLink()
     * @see cell()
     * @see write()
     * @see image()
     */
    public function link($x, $y, $width, $height, $link)
    {
        if ($x < 0) {
            $x += $this->w;
        }
        if ($y < 0) {
            $y += $this->h;
        }

        /* Set up the coordinates with correct scaling in pt. */
        $x      = $this->_toPt($x);
        $y      = $this->hPt - $this->_toPt($y);
        $width  = $this->_toPt($width);
        $height = $this->_toPt($height);

        /* Save link to page links array. */
        $this->_link($x, $y, $width, $height, $link);
    }

    /**
     * Prints a character string.
     *
     * The origin is on the left of the first character, on the baseline. This
     * method allows to place a string precisely on the page, but it is
     * usually easier to use {@link cell()}, {@link multiCell()} or {@link
     * write()} which are the standard methods to print text.
     *
     * All coordinates can be negative to provide values from the right or
     * bottom edge of the page (since File_Pdf 0.2.0, Horde 3.2).
     *
     * @param float $x      Abscissa of the origin.
     * @param float $y      Ordinate of the origin.
     * @param string $text  String to print.
     *
     * @see setFont()
     * @see cell()
     * @see multiCell()
     * @see write()
     */
    public function text($x, $y, $text)
    {
        if ($x < 0) {
            $x += $this->w;
        }
        if ($y < 0) {
            $y += $this->h;
        }

        /* Scale coordinates into points and set correct Y position. */
        $x = $this->_toPt($x);
        $y = $this->hPt - $this->_toPt($y);

        /* Escape any potentially harmful characters. */
        $text = $this->_escape($text);

        $out = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x, $y, $text);
        if ($this->_underline && $text != '') {
            $out .= ' ' . $this->_doUnderline($x, $y, $text);
        }
        if ($this->_color_flag) {
            $out = sprintf('q %s %s Q', $this->_text_color, $out);
        }
        $this->_out($out);
    }

    /**
     * Whenever a page break condition is met, the method is called, and the
     * break is issued or not depending on the returned value. The default
     * implementation returns a value according to the mode selected by
     * {@link setAutoPageBreak()}.
     * This method is called automatically and should not be called directly
     * by the application.
     *
     * @return boolean
     *
     * @see setAutoPageBreak()
     */
    public function acceptPageBreak()
    {
        return $this->_auto_page_break;
    }

    /**
     * Prints a cell (rectangular area) with optional borders, background
     * color and character string.
     *
     * The upper-left corner of the cell corresponds to the current
     * position. The text can be aligned or centered. After the call, the
     * current position moves to the right or to the next line. It is possible
     * to put a link on the text.  If automatic page breaking is enabled and
     * the cell goes beyond the limit, a page break is done before outputting.
     *
     * @param float $width   Cell width. If 0, the cell extends up to the right
     *                       margin.
     * @param float $height  Cell height.
     * @param string $text   String to print.
     * @param mixed $border  Indicates if borders must be drawn around the
     *                       cell. The value can be either a number:
     *                         - 0: no border (default)
     *                         - 1: frame
     *                       or a string containing some or all of the
     *                       following characters (in any order):
     *                         - L: left
     *                         - T: top
     *                         - R: right
     *                         - B: bottom
     * @param integer $ln    Indicates where the current position should go
     *                       after the call. Possible values are:
     *                         - 0: to the right (default)
     *                         - 1: to the beginning of the next line
     *                         - 2: below
     *                       Putting 1 is equivalent to putting 0 and calling
     *                       {@link newLine()} just after.
     * @param string $align  Allows to center or align the text. Possible
     *                       values are:
     *                         - L or empty string: left (default)
     *                         - C: center
     *                         - R: right
     * @param integer $fill  Indicates if the cell fill type. Possible values
     *                       are:
     *                         - 0: transparent (default)
     *                         - 1: painted
     * @param string $link   URL or identifier returned by {@link addLink()}.
     *
     * @see setFont()
     * @see setDrawColor()
     * @see setFillColor()
     * @see setLineWidth()
     * @see addLink()
     * @see newLine()
     * @see multiCell()
     * @see write()
     * @see setAutoPageBreak()
     */
    public function cell($width, $height = 0, $text = '', $border = 0, $ln = 0,
                  $align = '', $fill = 0, $link = '')
    {
        $k = $this->_scale;
        if ($this->y + $height > $this->_page_break_trigger &&
            !$this->_in_footer && $this->acceptPageBreak()) {
            $x = $this->x;
            $ws = $this->_word_spacing;
            if ($ws > 0) {
                $this->_word_spacing = 0;
                $this->_out('0 Tw');
            }
            $this->addPage($this->_current_orientation);
            $this->x = $x;
            if ($ws > 0) {
                $this->_word_spacing = $ws;
                $this->_out(sprintf('%.3F Tw', $ws * $k));
            }
        }
        if ($width == 0) {
            $width = $this->w - $this->_right_margin - $this->x;
        }
        $s = '';
        if ($fill == 1 || $border == 1) {
            if ($fill == 1) {
                $op = ($border == 1) ? 'B' : 'f';
            } else {
                $op = 'S';
            }
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $width * $k, -$height * $k, $op);
        }
        if (is_string($border)) {
            if (strpos($border, 'L') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $this->x * $k, ($this->h - $this->y) * $k, $this->x * $k, ($this->h - ($this->y + $height)) * $k);
            }
            if (strpos($border, 'T') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $this->x * $k, ($this->h - $this->y) * $k, ($this->x + $width) * $k, ($this->h - $this->y) * $k);
            }
            if (strpos($border, 'R') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($this->x + $width) * $k, ($this->h - $this->y) * $k, ($this->x + $width) * $k, ($this->h - ($this->y + $height)) * $k);
            }
            if (strpos($border, 'B') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $this->x * $k, ($this->h - ($this->y + $height)) * $k, ($this->x + $width) * $k, ($this->h - ($this->y + $height)) * $k);
            }
        }
        if ($text != '') {
            if ($align == 'R') {
                $dx = $width - $this->_cell_margin - $this->getStringWidth($text);
            } elseif ($align == 'C') {
                $dx = ($width - $this->getStringWidth($text)) / 2;
            } else {
                $dx = $this->_cell_margin;
            }
            if ($this->_color_flag) {
                $s .= 'q ' . $this->_text_color . ' ';
            }
            $text = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $text)));
            $test2 = ((.5 * $height) + (.3 * $this->_font_size));
            $test1 = $this->fhPt - (($this->y + $test2) * $k);
            $x = ($this->x + $dx) * $k;
            $y = ($this->h - ($this->y + .5 * $height + .3 * $this->_font_size)) * $k;
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', $x, $y, $text);
            if ($this->_underline) {
                $s .= ' ' . $this->_doUnderline($x, $y, $text);
            }
            if ($this->_color_flag) {
                $s .= ' Q';
            }
            if ($link) {
                $this->link($this->x + $dx, $this->y + .5 * $height- .5 * $this->_font_size, $this->getStringWidth($text), $this->_font_size, $link);
            }
        }
        if ($s) {
            $this->_out($s);
        }
        $this->_last_height = $height;
        if ($ln > 0) {
            // Go to next line.
            $this->y += $height;
            if ($ln == 1) {
                $this->x = $this->_left_margin;
            }
        } else {
            $this->x += $width;
        }
    }

    /**
     * This method allows printing text with line breaks.
     *
     * They can be automatic (as soon as the text reaches the right border of
     * the cell) or explicit (via the \n character). As many cells as
     * necessary are output, one below the other. Text can be aligned,
     * centered or justified. The cell block can be framed and the background
     * painted.
     *
     * @param float $width   Width of cells. If 0, they extend up to the right
     *                       margin of the page.
     * @param float $height  Height of cells.
     * @param string $text   String to print.
     * @param mixed $border  Indicates if borders must be drawn around the cell
     *                       block. The value can be either a number:
     *                         - 0: no border (default)
     *                         - 1: frame
     *                       or a string containing some or all of the
     *                       following characters (in any order):
     *                         - L: left
     *                         - T: top
     *                         - R: right
     *                         - B: bottom
     * @param string $align  Sets the text alignment. Possible values are:
     *                         - L: left alignment
     *                         - C: center
     *                         - R: right alignment
     *                         - J: justification (default value)
     * @param integer $fill  Indicates if the cell background must:
     *                         - 0: transparent (default)
     *                         - 1: painted
     *
     * @see setFont()
     * @see setDrawColor()
     * @see setFillColor()
     * @see setLineWidth()
     * @see cell()
     * @see write()
     * @see setAutoPageBreak()
     */
    public function multiCell($width, $height, $text, $border = 0, $align = 'J',
                       $fill = 0)
    {
        $cw = $this->_current_font['cw'];
        if ($width == 0) {
            $width = $this->w - $this->_right_margin - $this->x;
        }
        $wmax = ($width-2 * $this->_cell_margin) * 1000 / $this->_font_size;
        $s = str_replace("\r", '', $text);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb-1] == "\n") {
            $nb--;
        }
        $b = 0;
        if ($border) {
            if ($border == 1) {
                $border = 'LTRB';
                $b = 'LRT';
                $b2 = 'LR';
            } else {
                $b2 = '';
                if (strpos($border, 'L') !== false) {
                    $b2 .= 'L';
                }
                if (strpos($border, 'R') !== false) {
                    $b2 .= 'R';
                }
                $b = (strpos($border, 'T') !== false) ? $b2 . 'T' : $b2;
            }
        }
        $sep = -1;
        $i   = 0;
        $j   = 0;
        $l   = 0;
        $ns  = 0;
        $nl  = 1;
        while ($i < $nb) {
            // Get next character.
            $c = $s[$i];
            if ($c == "\n") {
                // Explicit line break.
                if ($this->_word_spacing > 0) {
                    $this->_word_spacing = 0;
                    $this->_out('0 Tw');
                }
                $this->cell($width, $height, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2) {
                    $b = $b2;
                }
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                // Automatic line break.
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                    if ($this->_word_spacing > 0) {
                        $this->_word_spacing = 0;
                        $this->_out('0 Tw');
                    }
                    $this->cell($width, $height, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                } else {
                    if ($align == 'J') {
                        $this->_word_spacing = ($ns>1) ? ($wmax - $ls)/1000 * $this->_font_size / ($ns - 1) : 0;
                        $this->_out(sprintf('%.3F Tw', $this->_word_spacing * $this->_scale));
                    }
                    $this->cell($width, $height, substr($s, $j, $sep - $j), $b, 2, $align, $fill);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2) {
                    $b = $b2;
                }
            } else {
                $i++;
            }
        }
        // Last chunk.
        if ($this->_word_spacing > 0) {
            $this->_word_spacing = 0;
            $this->_out('0 Tw');
        }
        if ($border && strpos($border, 'B') !== false) {
            $b .= 'B';
        }
        $this->cell($width, $height, substr($s, $j, $i), $b, 2, $align, $fill);
        $this->x = $this->_left_margin;
    }

    /**
     * This method prints text from the current position.
     *
     * When the right margin is reached (or the \n character is met) a line
     * break occurs and text continues from the left margin. Upon method exit,
     * the current position is left just at the end of the text.
     *
     * It is possible to put a link on the text.
     *
     * Example:
     * <code>
     * // Begin with regular font
     * $pdf->setFont('Arial', '', 14);
     * $pdf->write(5, 'Visit ');
     * // Then put a blue underlined link
     * $pdf->setTextColor(0, 0, 255);
     * $pdf->setFont('', 'U');
     * $pdf->write(5, 'www.fpdf.org', 'http://www.fpdf.org');
     * </code>
     *
     * @param float $height  Line height.
     * @param string $text   String to print.
     * @param mixed $link    URL or identifier returned by {@link addLink()}.
     *
     * @see setFont()
     * @see addLink()
     * @see multiCell()
     * @see setAutoPageBreak()
     */
    public function write($height, $text, $link = '')
    {
        $cw = $this->_current_font['cw'];
        $width = $this->w - $this->_right_margin - $this->x;
        $wmax = ($width - 2 * $this->_cell_margin) * 1000 / $this->_font_size;
        $s = str_replace("\r", '', $text);
        $nb = strlen($s);
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            // Get next character.
            $c = $s{$i};
            if ($c == "\n") {
                // Explicit line break.
                $this->cell($width, $height, substr($s, $j, $i - $j), 0, 2, '', 0, $link);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $this->x = $this->_left_margin;
                    $width = $this->w - $this->_right_margin - $this->x;
                    $wmax = ($width - 2 * $this->_cell_margin) * 1000 / $this->_font_size;
                }
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
                $ls = $l;
            }
            $l += (isset($cw[$c]) ? $cw[$c] : 0);
            if ($l > $wmax) {
                // Automatic line break.
                if ($sep == -1) {
                    if ($this->x > $this->_left_margin) {
                        // Move to next line.
                        $this->x = $this->_left_margin;
                        $this->y += $height;
                        $width = $this->w - $this->_right_margin - $this->x;
                        $wmax = ($width - 2 * $this->_cell_margin) * 1000 / $this->_font_size;
                        $i++;
                        $nl++;
                        continue;
                    }
                    if ($i == $j) {
                        $i++;
                    }
                    $this->cell($width, $height, substr($s, $j, $i - $j), 0, 2, '', 0, $link);
                } else {
                    $this->cell($width, $height, substr($s, $j, $sep - $j), 0, 2, '', 0, $link);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $this->x = $this->_left_margin;
                    $width = $this->w - $this->_right_margin - $this->x;
                    $wmax = ($width - 2 * $this->_cell_margin) * 1000 / $this->_font_size;
                }
                $nl++;
            } else {
                $i++;
            }
        }
        // Last chunk.
        if ($i != $j) {
            $this->cell($l / 1000 * $this->_font_size, $height, substr($s, $j, $i), 0, 0, '', 0, $link);
        }
    }

    /**
     * Writes text at an angle.
     *
     * All coordinates can be negative to provide values from the right or
     * bottom edge of the page (since File_Pdf 0.2.0, Horde 3.2).
     *
     * @param integer $x         X coordinate.
     * @param integer $y         Y coordinate.
     * @param string $text       Text to write.
     * @param float $text_angle  Angle to rotate (Eg. 90 = bottom to top).
     * @param float $font_angle  Rotate characters as well as text.
     *
     * @see setFont()
     */
    public function writeRotated($x, $y, $text, $text_angle, $font_angle = 0)
    {
        if ($x < 0) {
            $x += $this->w;
        }
        if ($y < 0) {
            $y += $this->h;
        }

        // Escape text.
        $text = $this->_escape($text);

        $font_angle += 90 + $text_angle;
        $text_angle *= M_PI / 180;
        $font_angle *= M_PI / 180;

        $text_dx = cos($text_angle);
        $text_dy = sin($text_angle);
        $font_dx = cos($font_angle);
        $font_dy = sin($font_angle);

        $s= sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',
                    $text_dx, $text_dy, $font_dx, $font_dy,
                    $x * $this->_scale, ($this->h-$y) * $this->_scale, $text);

        if ($this->_draw_color) {
            $s = 'q ' . $this->_draw_color . ' ' . $s . ' Q';
        }
        $this->_out($s);
    }

    /**
     * Prints an image in the page.
     *
     * The upper-left corner and at least one of the dimensions must be
     * specified; the height or the width can be calculated automatically in
     * order to keep the image proportions. Supported formats are JPEG and
     * PNG.
     *
     * All coordinates can be negative to provide values from the right or
     * bottom edge of the page (since File_Pdf 0.2.0, Horde 3.2).
     *
     * For JPEG, all flavors are allowed:
     *   - gray scales
     *   - true colors (24 bits)
     *   - CMYK (32 bits)
     *
     * For PNG, are allowed:
     *   - gray scales on at most 8 bits (256 levels)
     *   - indexed colors
     *   - true colors (24 bits)
     * but are not supported:
     *   - Interlacing
     *   - Alpha channel
     *
     * If a transparent color is defined, it will be taken into account (but
     * will be only interpreted by Acrobat 4 and above).
     * The format can be specified explicitly or inferred from the file
     * extension.
     * It is possible to put a link on the image.
     *
     * Remark: if an image is used several times, only one copy will be
     * embedded in the file.
     *
     * @param string $file   Name of the file containing the image.
     * @param float $x       Abscissa of the upper-left corner.
     * @param float $y       Ordinate of the upper-left corner.
     * @param float $width   Width of the image in the page. If equal to zero,
     *                       it is automatically calculated to keep the
     *                       original proportions.
     * @param float $height  Height of the image in the page. If not specified
     *                       or equal to zero, it is automatically calculated
     *                       to keep the original proportions.
     * @param string $type   Image format. Possible values are (case
     *                       insensitive): JPG, JPEG, PNG. If not specified,
     *                       the type is inferred from the file extension.
     * @param mixed $link    URL or identifier returned by {@link addLink()}.
     *
     * @see addLink()
     */
    public function image($file, $x, $y, $width = 0, $height = 0, $type = '',
                   $link = '')
    {
        if ($x < 0) {
            $x += $this->w;
        }
        if ($y < 0) {
            $y += $this->h;
        }

        if (!isset($this->_images[$file])) {
            // First use of image, get some file info.
            if ($type == '') {
                $pos = strrpos($file, '.');
                if ($pos === false) {
                    throw new Horde_Pdf_Exception(sprintf('Image file has no extension and no type was specified: %s', $file));
                }
                $type = substr($file, $pos + 1);
            }

            $mqr = get_magic_quotes_runtime();
            if ($mqr) { set_magic_quotes_runtime(0); }

            $type = strtolower($type);
            if ($type == 'jpg' || $type == 'jpeg') {
                $info = $this->_parseJPG($file);
            } elseif ($type == 'png') {
                $info = $this->_parsePNG($file);
            } else {
                throw new Horde_Pdf_Exception(sprintf('Unsupported image file type: %s', $type));
            }

            if ($mqr) { set_magic_quotes_runtime($mqr); }

            $info['i'] = count($this->_images) + 1;
            $this->_images[$file] = $info;
        } else {
            $info = $this->_images[$file];
        }

        // Make sure all vars are converted to pt scale.
        $x      = $this->_toPt($x);
        $y      = $this->hPt - $this->_toPt($y);
        $width  = $this->_toPt($width);
        $height = $this->_toPt($height);

        // If not specified do automatic width and height calculations.
        if (empty($width) && empty($height)) {
            $width = $info['w'];
            $height = $info['h'];
        } elseif (empty($width)) {
            $width = $height * $info['w'] / $info['h'];
        } elseif (empty($height)) {
            $height = $width * $info['h'] / $info['w'];
        }

        $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q', $width, $height, $x, $y - $height, $info['i']));

        // Set any link if requested.
        if ($link) {
            $this->_link($x, $y, $width, $height, $link);
        }
    }

    /**
     * Performs a line break.
     *
     * The current abscissa goes back to the left margin and the ordinate
     * increases by the amount passed in parameter.
     *
     * @param float $height  The height of the break. By default, the value
     *                       equals the height of the last printed cell.
     *
     * @see cell()
     */
    public function newLine($height = '')
    {
        $this->x = $this->_left_margin;
        if (is_string($height)) {
            $this->y += $this->_last_height;
        } else {
            $this->y += $height;
        }
    }

    /**
     * Get the current page
     *
     * @return  integer
     */
    public function getPage()
    {
        return $this->_page;
    }

    /**
     * Set the current page
     * @param   integer $page
     */
    public function setPage($page)
    {
        $this->_page = $page;
    }

    /**
     * Returns the abscissa of the current position in user units.
     *
     * @return float
     *
     * @see setX()
     * @see getY()
     * @see setY()
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Defines the abscissa of the current position.
     *
     * If the passed value is negative, it is relative to the right of the
     * page.
     *
     * @param float $x  The value of the abscissa.
     *
     * @see getX()
     * @see getY()
     * @see setY()
     * @see setXY()
     */
    public function setX($x)
    {
        if ($x >= 0) {
            // Absolute value.
            $this->x = $x;
        } else {
            // Negative, so relative to right edge of the page.
            $this->x = $this->w + $x;
        }
    }

    /**
     * Returns the ordinate of the current position in user units.
     *
     * @return float
     *
     * @see setY()
     * @see getX()
     * @see setX()
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Defines the ordinate of the current position.
     *
     * If the passed value is negative, it is relative to the bottom of the
     * page.
     *
     * @param float $y  The value of the ordinate.
     *
     * @see getX()
     * @see getY()
     * @see setY()
     * @see setXY()
     */
    public function setY($y)
    {
        if ($y >= 0) {
            // Absolute value.
            $this->y = $y;
        } else {
            // Negative, so relative to bottom edge of the page.
            $this->y = $this->h + $y;
        }
    }

    /**
     * Defines the abscissa and ordinate of the current position.
     *
     * If the passed values are negative, they are relative respectively to
     * the right and bottom of the page.
     *
     * @param float $x  The value of the abscissa.
     * @param float $y  The value of the ordinate.
     *
     * @see setX()
     * @see setY()
     */
    public function setXY($x, $y)
    {
        $this->setY($y);
        $this->setX($x);
    }

    /**
     * Returns the current buffer content and resets the buffer.
     *
     * Use this method when creating large files to avoid memory problems.
     * This method doesn't work in combination with the save() method,
     * use getOutput() at the end. Calling this method doubles the
     * memory usage during the call.
     *
     * @see getOutput()
     */
    public function flush()
    {
        // Make sure we have the file header.
        $this->_beginDoc();

        $buffer = $this->_buffer;
        $this->_buffer = '';
        $this->_flushed = true;
        $this->_buflen += strlen($buffer);

        return $buffer;
    }

    /**
     * Returns the raw Pdf file.
     *
     * @see flush()
     */
    public function getOutput()
    {
        // Check whether file has been closed.
        if ($this->_state < 3) {
            $this->close();
        }

        return $this->_buffer;
    }

    /**
     * Saves the PDF file on the filesystem.
     *
     * @param string $filename  The filename for the output file.
     */
    public function save($filename = 'unknown.pdf')
    {
        // Check whether the buffer has been flushed already.
        if ($this->_flushed) {
            throw new Horde_Pdf_Exception('The buffer has been flushed already, don\'t use save() in combination with flush().');
        }

        // Check whether file has been closed.
        if ($this->_state < 3) {
            $this->close();
        }

        $f = fopen($filename, 'wb');
        if (!$f) {
            throw new Horde_Pdf_Exception(sprintf('Unable to save Pdf file: %s', $filename));
        }
        fwrite($f, $this->_buffer, strlen($this->_buffer));
        fclose($f);
    }

    /**
     * Scale a value.
     *
     * @param  integer  $val  Value
     * @return integer        Value multiplied by scale
     */
    protected function _toPt($val)
    {
        return $val * $this->_scale;
    }

    /**
     * Load information about a font from its key name.
     *
     * @param  string  $fontkey  Font name key
     * @return array             Array of all font widths, including this font.
     */
    protected static function _getFontFile($fontkey)
    {
        if (!isset(self::$_font_widths[$fontkey])) {
            $fontClass = 'Horde_Pdf_Font_' . ucfirst(strtolower($fontkey));
            if (!class_exists($fontClass)) {
                throw new Horde_Pdf_Exception(sprintf('Could not include font metric class: %s', $fontClass));
            }

            $font = new $fontClass;

            self::$_font_widths = array_merge(self::$_font_widths, $font->getWidths());
            if (!isset(self::$_font_widths[$fontkey])) {
                throw new Horde_Pdf_Exception(sprintf('Could not include font metric class: %s', $fontClass));
            }
        }

        return self::$_font_widths;
    }

    /**
     * Save link to page links array.
     *
     * @param  integer  $x       X-coordinate
     * @param  integer  $y       Y-coordinate
     * @param  integer  $width   Width
     * @param  integer  $height  Height
     * @param  string   $link    Link
     * @return void
     */
    protected function _link($x, $y, $width, $height, $link)
    {
        $this->_page_links[$this->_page][] = array($x, $y, $width, $height, $link);
    }

    /**
     * Begin the PDF document.
     *
     * @return void
     */
    protected function _beginDoc()
    {
        // Start document, but only if not yet started.
        if ($this->_state < 1) {
            $this->_state = 1;
            $this->_out('%PDF-1.3');
        }
    }

    /**
     * Write the PDF pages.
     *
     * @return void
     */
    protected function _putPages()
    {
        $nb = $this->_page;
        if (!empty($this->_alias_nb_pages)) {
            // Replace number of pages.
            for ($n = 1; $n <= $nb; $n++) {
                $this->_pages[$n] = str_replace($this->_alias_nb_pages, $nb, $this->_pages[$n]);
            }
        }
        if ($this->_default_orientation == 'P') {
            $wPt = $this->fwPt;
            $hPt = $this->fhPt;
        } else {
            $wPt = $this->fhPt;
            $hPt = $this->fwPt;
        }
        $filter = ($this->_compress) ? '/Filter /FlateDecode ' : '';
        for ($n = 1; $n <= $nb; $n++) {
            // Page
            $this->_newobj();
            $this->_out('<</Type /Page');
            $this->_out('/Parent 1 0 R');
            if (isset($this->_orientation_changes[$n])) {
                $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $hPt, $wPt));
            }
            $this->_out('/Resources 2 0 R');
            if (isset($this->_page_links[$n])) {
                // Links
                $annots = '/Annots [';
                foreach ($this->_page_links[$n] as $pl) {
                    $rect = sprintf('%.2F %.2F %.2F %.2F', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);
                    $annots .= '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';
                    if (is_string($pl[4])) {
                        $annots .= '/A <</S /URI /URI ' . $this->_textString($pl[4]) . '>>>>';
                    } else {
                        $l = $this->_links[$pl[4]];
                        $height = isset($this->_orientation_changes[$l[0]]) ? $wPt : $hPt;
                        $annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>', 1 + 2 * $l[0], $height - $l[1] * $this->_scale);
                    }
                }
                $this->_out($annots . ']');
            }
            $this->_out('/Contents ' . ($this->_n + 1) . ' 0 R>>');
            $this->_out('endobj');
            // Page content
            $p = ($this->_compress) ? gzcompress($this->_pages[$n]) : $this->_pages[$n];
            $this->_newobj();
            $this->_out('<<' . $filter . '/Length ' . strlen($p) . '>>');
            $this->_putStream($p);
            $this->_out('endobj');
        }
        // Pages root
        $this->_offsets[1] = $this->_buflen + strlen($this->_buffer);
        $this->_out('1 0 obj');
        $this->_out('<</Type /Pages');
        $kids = '/Kids [';
        for ($i = 0; $i < $nb; $i++) {
            $kids .= (3 + 2 * $i) . ' 0 R ';
        }
        $this->_out($kids . ']');
        $this->_out('/Count ' . $nb);
        $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $wPt, $hPt));
        $this->_out('>>');
        $this->_out('endobj');
    }

    /**
     * Write the PDF fonts.
     *
     * @return void
     */
    protected function _putFonts()
    {
        $nf = $this->_n;
        foreach ($this->_diffs as $diff) {
            // Encodings
            $this->_newobj();
            $this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $diff . ']>>');
            $this->_out('endobj');
        }

        $mqr = get_magic_quotes_runtime();
        if ($mqr) { set_magic_quotes_runtime(0); }

        foreach ($this->_font_files as $file => $info) {
            // Font file embedding.
            $this->_newobj();
            $this->_font_files[$file]['n'] = $this->_n;
            $size = filesize($file);
            if (!$size) {
                throw new Horde_Pdf_Exception('Font file not found');
            }
            $this->_out('<</Length ' . $size);
            if (substr($file, -2) == '.z') {
                $this->_out('/Filter /FlateDecode');
            }
            $this->_out('/Length1 ' . $info['length1']);
            if (isset($info['length2'])) {
                $this->_out('/Length2 ' . $info['length2'] . ' /Length3 0');
            }
            $this->_out('>>');
            $f = fopen($file, 'rb');
            $this->_putStream(fread($f, $size));
            fclose($f);
            $this->_out('endobj');
        }

        if ($mqr) { set_magic_quotes_runtime($mqr); }

        foreach ($this->_fonts as $k => $font) {
            // Font objects
            $this->_newobj();
            $this->_fonts[$k]['n'] = $this->_n;
            $name = $font['name'];
            $this->_out('<</Type /Font');
            $this->_out('/BaseFont /' . $name);
            if ($font['type'] == 'core') {
                // Standard font.
                $this->_out('/Subtype /Type1');
                if ($name != 'Symbol' && $name != 'ZapfDingbats') {
                    $this->_out('/Encoding /WinAnsiEncoding');
                }
            } else {
                // Additional font.
                $this->_out('/Subtype /' . $font['type']);
                $this->_out('/FirstChar 32');
                $this->_out('/LastChar 255');
                $this->_out('/Widths ' . ($this->_n + 1) . ' 0 R');
                $this->_out('/FontDescriptor ' . ($this->_n + 2) . ' 0 R');
                if ($font['enc']) {
                    if (isset($font['diff'])) {
                        $this->_out('/Encoding ' . ($nf + $font['diff']) . ' 0 R');
                    } else {
                        $this->_out('/Encoding /WinAnsiEncoding');
                    }
                }
            }
            $this->_out('>>');
            $this->_out('endobj');
            if ($font['type'] != 'core') {
                // Widths.
                $this->_newobj();
                $cw = $font['cw'];
                $s = '[';
                for ($i = 32; $i <= 255; $i++) {
                    $s .= $cw[chr($i)] . ' ';
                }
                $this->_out($s . ']');
                $this->_out('endobj');
                // Descriptor.
                $this->_newobj();
                $s = '<</Type /FontDescriptor /FontName /' . $name;
                foreach ($font['desc'] as $k => $v) {
                    $s .= ' /' . $k . ' ' . $v;
                }
                $file = $font['file'];
                if ($file) {
                    $s .= ' /FontFile' . ($font['type'] == 'Type1' ? '' : '2') . ' ' . $this->_font_files[$file]['n'] . ' 0 R';
                }
                $this->_out($s . '>>');
                $this->_out('endobj');
            }
        }
    }

    /**
     * Write the PDF images.
     *
     * @return void
     */
    protected function _putImages()
    {
        $filter = ($this->_compress) ? '/Filter /FlateDecode ' : '';
        foreach ($this->_images as $file => $info) {
            $this->_newobj();
            $this->_images[$file]['n'] = $this->_n;
            $this->_out('<</Type /XObject');
            $this->_out('/Subtype /Image');
            $this->_out('/Width ' . $info['w']);
            $this->_out('/Height ' . $info['h']);
            if ($info['cs'] == 'Indexed') {
                $this->_out('/ColorSpace [/Indexed /DeviceRGB ' . (strlen($info['pal'])/3 - 1) . ' ' . ($this->_n + 1) . ' 0 R]');
            } else {
                $this->_out('/ColorSpace /' . $info['cs']);
                if ($info['cs'] == 'DeviceCMYK') {
                    $this->_out('/Decode [1 0 1 0 1 0 1 0]');
                }
            }
            $this->_out('/BitsPerComponent ' . $info['bpc']);
            $this->_out('/Filter /' . $info['f']);
            if (isset($info['parms'])) {
                $this->_out($info['parms']);
            }
            if (isset($info['trns']) && is_array($info['trns'])) {
                $trns = '';
                $i_max = count($info['trns']);
                for ($i = 0; $i < $i_max; $i++) {
                    $trns .= $info['trns'][$i] . ' ' . $info['trns'][$i] . ' ';
                }
                $this->_out('/Mask [' . $trns . ']');
            }
            $this->_out('/Length ' . strlen($info['data']) . '>>');
            $this->_putStream($info['data']);
            $this->_out('endobj');

            // Palette.
            if ($info['cs'] == 'Indexed') {
                $this->_newobj();
                $pal = ($this->_compress) ? gzcompress($info['pal']) : $info['pal'];
                $this->_out('<<' . $filter . '/Length ' . strlen($pal) . '>>');
                $this->_putStream($pal);
                $this->_out('endobj');
            }
        }
    }

    /**
     * Write the PDF resources.
     *
     * @return void
     */
    protected function _putResources()
    {
        $this->_putFonts();
        $this->_putImages();
        // Resource dictionary
        $this->_offsets[2] = $this->_buflen + strlen($this->_buffer);
        $this->_out('2 0 obj');
        $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_out('/Font <<');
        foreach ($this->_fonts as $font) {
            $this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
        }
        $this->_out('>>');
        if (count($this->_images)) {
            $this->_out('/XObject <<');
            foreach ($this->_images as $image) {
                $this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
            }
            $this->_out('>>');
        }
        $this->_out('>>');
        $this->_out('endobj');
    }

    /**
     * Write the PDF information.
     *
     * @return void
     */
    protected function _putInfo()
    {
        $this->_out('/Producer ' . $this->_textString('Horde PDF'));
        if (!empty($this->_info['title'])) {
            $this->_out('/Title ' . $this->_textString($this->_info['title']));
        }
        if (!empty($this->_info['subject'])) {
            $this->_out('/Subject ' . $this->_textString($this->_info['subject']));
        }
        if (!empty($this->_info['author'])) {
            $this->_out('/Author ' . $this->_textString($this->_info['author']));
        }
        if (!empty($this->keywords)) {
            $this->_out('/Keywords ' . $this->_textString($this->keywords));
        }
        if (!empty($this->creator)) {
            $this->_out('/Creator ' . $this->_textString($this->creator));
        }
        if (!isset($this->_info['CreationDate'])) {
            $this->_info['CreationDate'] = 'D:' . date('YmdHis', time());
        }
        $this->_out('/CreationDate ' . $this->_textString($this->_info['CreationDate']));
    }

    /**
     * Write the PDF catalog.
     *
     * @return void
     */
    protected function _putCatalog()
    {
        $this->_out('/Type /Catalog');
        $this->_out('/Pages 1 0 R');
        if ($this->_zoom_mode == 'fullpage') {
            $this->_out('/OpenAction [3 0 R /Fit]');
        } elseif ($this->_zoom_mode == 'fullwidth') {
            $this->_out('/OpenAction [3 0 R /FitH null]');
        } elseif ($this->_zoom_mode == 'real') {
            $this->_out('/OpenAction [3 0 R /XYZ null null 1]');
        } elseif (!is_string($this->_zoom_mode)) {
            $this->_out('/OpenAction [3 0 R /XYZ null null ' . ($this->_zoom_mode / 100) . ']');
        }
        if ($this->_layout_mode == 'single') {
            $this->_out('/PageLayout /SinglePage');
        } elseif ($this->_layout_mode == 'continuous') {
            $this->_out('/PageLayout /OneColumn');
        } elseif ($this->_layout_mode == 'two') {
            $this->_out('/PageLayout /TwoColumnLeft');
        }
    }

    /**
     * Write the PDF trailer.
     *
     * @return void
     */
    protected function _putTrailer()
    {
        $this->_out('/Size ' . ($this->_n + 1));
        $this->_out('/Root ' . $this->_n . ' 0 R');
        $this->_out('/Info ' . ($this->_n - 1) . ' 0 R');
    }

    /**
     * End the PDF document
     *
     * @return void
     */
    protected function _endDoc()
    {
        $this->_putPages();
        $this->_putResources();
        // Info
        $this->_newobj();
        $this->_out('<<');
        $this->_putInfo();
        $this->_out('>>');
        $this->_out('endobj');
        // Catalog
        $this->_newobj();
        $this->_out('<<');
        $this->_putCatalog();
        $this->_out('>>');
        $this->_out('endobj');
        // Cross-ref
        $o = $this->_buflen + strlen($this->_buffer);
        $this->_out('xref');
        $this->_out('0 ' . ($this->_n + 1));
        $this->_out('0000000000 65535 f ');
        for ($i = 1; $i <= $this->_n; $i++) {
            $this->_out(sprintf('%010d 00000 n ', $this->_offsets[$i]));
        }
        // Trailer
        $this->_out('trailer');
        $this->_out('<<');
        $this->_putTrailer();
        $this->_out('>>');
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');
        $this->_state = 3;
    }

    /**
     * Begin a new page.
     *
     * @param  string  $orientation  Orientation code
     * @return void
     */
    protected function _beginPage($orientation)
    {
        $this->_page++;

        // only assign page contents if it is new
        if (!isset($this->_pages[$this->_page])) {
            $this->_pages[$this->_page] = '';
        }

        $this->_state = 2;
        $this->x = $this->_left_margin;
        $this->y = $this->_top_margin;
        $this->_last_height = 0;
        // Page orientation
        if (!$orientation) {
            $orientation = $this->_default_orientation;
        } else {
            $orientation = strtoupper($orientation[0]);
            if ($orientation != $this->_default_orientation) {
                $this->_orientation_changes[$this->_page] = true;
            }
        }
        if ($orientation != $this->_current_orientation) {
            // Change orientation
            if ($orientation == 'P') {
                $this->wPt = $this->fwPt;
                $this->hPt = $this->fhPt;
                $this->w   = $this->fw;
                $this->h   = $this->fh;
            } else {
                $this->wPt = $this->fhPt;
                $this->hPt = $this->fwPt;
                $this->w   = $this->fh;
                $this->h   = $this->fw;
            }
            $this->_page_break_trigger = $this->h - $this->_break_margin;
            $this->_current_orientation = $orientation;
        }
    }

    /**
     * Set the end of page contents.
     *
     * @return void
     */
    protected function _endPage()
    {
        $this->_state = 1;
    }

    /**
     * Begin a new object.
     *
     * @return void
     */
    protected function _newobj()
    {
        $this->_n++;
        $this->_offsets[$this->_n] = $this->_buflen + strlen($this->_buffer);
        $this->_out($this->_n . ' 0 obj');
    }

    /**
     * Underline a block of text.
     *
     * @param  integer  $x     X-coordinate
     * @param  integer  $y     Y-coordinate
     * @param  string   $text  Text to underline
     * @return string          Underlined string
     */
    protected function _doUnderline($x, $y, $text)
    {
        // Set the rectangle width according to text width.
        $width  = $this->getStringWidth($text, true);

        /* Set rectangle position and height, using underline position and
         * thickness settings scaled by the font size. */
        $y = $y + ($this->_current_font['up'] * $this->_font_size_pt / 1000);
        $height = -$this->_current_font['ut'] * $this->_font_size_pt / 1000;

        return sprintf('%.2F %.2F %.2F %.2F re f', $x, $y, $width, $height);
    }

    /**
     * Extract info from a JPEG file.
     *
     * @param  string  $file  Filename of JPEG image
     * @return array         Assoc. array of info
     */
    protected function _parseJPG($file)
    {
        // Extract info from a JPEG file.
        $img = @getimagesize($file);
        if (!$img) {
            throw new Horde_Pdf_Exception(sprintf('Missing or incorrect image file: %s', $file));
        }
        if ($img[2] != 2) {
            throw new Horde_Pdf_Exception(sprintf('Not a JPEG file: %s', $file));
        }
        if (!isset($img['channels']) || $img['channels'] == 3) {
            $colspace = 'DeviceRGB';
        } elseif ($img['channels'] == 4) {
            $colspace = 'DeviceCMYK';
        } else {
            $colspace = 'DeviceGray';
        }
        $bpc = isset($img['bits']) ? $img['bits'] : 8;

        // Read whole file.
        $f = fopen($file, 'rb');
        $data = fread($f, filesize($file));
        fclose($f);

        return array('w' => $img[0], 'h' => $img[1], 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'DCTDecode', 'data' => $data);
    }

    /**
     * Extract info from a PNG file.
     *
     * @param  string  $file  Filename of PNG image
     * @return array          Assoc. array of info
     */
    protected function _parsePNG($file)
    {
        // Extract info from a PNG file.
        $f = fopen($file, 'rb');
        if (!$f) {
            throw new Horde_Pdf_Exception(sprintf('Unable to open image file: %s', $file));
        }

        // Check signature.
        if (fread($f, 8) != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10)) {
            throw new Horde_Pdf_Exception(sprintf('Not a PNG file: %s', $file));
        }

        // Read header chunk.
        fread($f, 4);
        if (fread($f, 4) != 'IHDR') {
            throw new Horde_Pdf_Exception(sprintf('Incorrect PNG file: %s', $file));
        }
        $width = $this->_freadInt($f);
        $height = $this->_freadInt($f);
        $bpc = ord(fread($f, 1));
        if ($bpc > 8) {
            throw new Horde_Pdf_Exception(sprintf('16-bit depth not supported: %s', $file));
        }
        $ct = ord(fread($f, 1));
        if ($ct == 0) {
            $colspace = 'DeviceGray';
        } elseif ($ct == 2) {
            $colspace = 'DeviceRGB';
        } elseif ($ct == 3) {
            $colspace = 'Indexed';
        } else {
            throw new Horde_Pdf_Exception(sprintf('Alpha channel not supported: %s', $file));
        }
        if (ord(fread($f, 1)) != 0) {
            throw new Horde_Pdf_Exception(sprintf('Unknown compression method: %s', $file));
        }
        if (ord(fread($f, 1)) != 0) {
            throw new Horde_Pdf_Exception(sprintf('Unknown filter method: %s', $file));
        }
        if (ord(fread($f, 1)) != 0) {
            throw new Horde_Pdf_Exception(sprintf('Interlacing not supported: %s', $file));
        }
        fread($f, 4);
        $parms = '/DecodeParms <</Predictor 15 /Colors ' . ($ct == 2 ? 3 : 1) . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $width . '>>';
        // Scan chunks looking for palette, transparency and image data.
        $pal = '';
        $trns = '';
        $data = '';
        do {
            $n = $this->_freadInt($f);
            $type = fread($f, 4);
            if ($type == 'PLTE') {
                // Read palette
                $pal = fread($f, $n);
                fread($f, 4);
            } elseif ($type == 'tRNS') {
                // Read transparency info
                $t = fread($f, $n);
                if ($ct == 0) {
                    $trns = array(ord(substr($t, 1, 1)));
                } elseif ($ct == 2) {
                    $trns = array(ord(substr($t, 1, 1)), ord(substr($t, 3, 1)), ord(substr($t, 5, 1)));
                } else {
                    $pos = strpos($t, chr(0));
                    if (is_int($pos)) {
                        $trns = array($pos);
                    }
                }
                fread($f, 4);
            } elseif ($type == 'IDAT') {
                // Read image data block
                $data .= fread($f, $n);
                fread($f, 4);
            } elseif ($type == 'IEND') {
                break;
            } else {
                fread($f, $n + 4);
            }
        } while ($n);

        if ($colspace == 'Indexed' && empty($pal)) {
            throw new Horde_Pdf_Exception(sprintf('Missing palette in: %s', $file));
        }
        fclose($f);

        return array('w' => $width, 'h' => $height, 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'FlateDecode', 'parms' => $parms, 'pal' => $pal, 'trns' => $trns, 'data' => $data);
    }

    /**
     * Read a 4-byte integer from stream.
     *
     * @param  resource  $f  Stream resource
     * @return integer       Byte
     */
    protected function _freadInt($f)
    {
        $i  = ord(fread($f, 1)) << 24;
        $i += ord(fread($f, 1)) << 16;
        $i += ord(fread($f, 1)) << 8;
        $i += ord(fread($f, 1));
        return $i;
    }

    /**
     * Format a text string by escaping and wrapping in parentheses.
     *
     * @param  string  $s  String to format.
     * @param  string      Formatted string.
     * @return string
     */
    protected function _textString($s)
    {
        return '(' . $this->_escape($s) . ')';
    }

    /**
     * Escape parentheses and forward slash.
     *
     * @param  string  $s  String to escape.
     * @return string      Escaped string.
     */
    protected function _escape($s)
    {
        return str_replace(array('\\', ')', '('),
                           array('\\\\', '\\)', '\\('),
                           $s);
    }

    /**
     * Add a line to the document wrapped in 'stream' and 'endstream'.
     *
     * @param  string  $s  Line to add.
     * @return void
     */
    protected function _putStream($s)
    {
        $this->_out('stream');
        $this->_out($s);
        $this->_out('endstream');
    }

    /**
     * Add a line to the document.
     *
     * @param  string  $s  Line to add.
     * @return void
     */
    protected function _out($s)
    {
        if ($this->_state == 2) {
            $this->_pages[$this->_page] .= $s . "\n";
        } else {
            $this->_buffer .= $s . "\n";
        }
    }

    /**
     * Convert hex-based color to RGB
     */
    protected function _hexToRgb($hex)
    {
        if (substr($hex, 0, 1) == '#') { $hex = substr($hex, 1); }

        if (strlen($hex) == 6) {
            list($r, $g, $b) = array(substr($hex, 0, 2),
                                     substr($hex, 2, 2),
                                     substr($hex, 4, 2));
        } elseif (strlen($hex) == 3) {
            list($r, $g, $b) = array(substr($hex, 0, 1).substr($hex, 0, 1),
                                     substr($hex, 1, 1).substr($hex, 1, 1),
                                     substr($hex, 2, 1).substr($hex, 2, 1));
        }
        $r = hexdec($r)/255;
        $g = hexdec($g)/255;
        $b = hexdec($b)/255;

        return array($r, $g, $b);
    }
}
