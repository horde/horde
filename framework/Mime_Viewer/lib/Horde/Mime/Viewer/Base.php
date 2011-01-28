<?php
/**
 * The Horde_Mime_Viewer_Base:: class provides the API for specific viewer
 * drivers to extend.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Base
{
    /**
     * Viewer configuration.
     *
     * @var array
     */
    protected $_conf = array();

    /**
     * The Horde_Mime_Part object to render.
     *
     * @var Horde_Mime_Part
     */
    protected $_mimepart = null;

    /**
     * Required configuration parameters.
     *
     * @var array
     */
    protected $_required = array();

    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
        'info' => false,
        'inline' => false,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        // Is the part *data* compressed (not the rendered data)?
        'compressed' => false,
        // Does this part contain emebedded MIME data?
        'embedded' => false,
        // Force inline display of this part?
        'forceinline' => false
    );

    /**
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * temp_file - (callback) A callback function that returns a temporary
     *             filename.  Is passed one parameter: a prefix string.
     *             DEFAULT: Uses Horde_Util::getTempFile().
     * text_filter - (callback) A callback function used to filter text. Is
     *               called the same as Horde_Text_Filter::filter().
     *               DEFAULT: Uses Horde_Text_Filter::filter().
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        foreach ($this->_required as $val) {
            if (!isset($conf[$val])) {
                throw new InvalidArgumentException(__CLASS__ . ': Missing configuration value (' . $val . ')');
            }
        }

        $this->_mimepart = $part;
        $this->_conf = $conf;
    }

    /**
     * Sets the Horde_Mime_Part object for the class.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     */
    public function setMimePart(Horde_Mime_Part $mime_part)
    {
        $this->_mimepart = $mime_part;
    }

    /**
     * Return the rendered version of the Horde_Mime_Part object.
     *
     * @param string $mode  The mode:
     * <pre>
     * 'full' - A full representation of the MIME part, for use in a view
     *          where the output to the browser can be set to the value
     *          returned in 'type'. This mode should only return a single
     *          MIME ID entry for viewing and should not return any status
     *          information.
     * 'inline' - A representation of the MIME part that can be viewed inline
     *            on a text/html page that may contain other HTML elements.
     * 'info' - A representation of the MIME part that can be viewed inline
     *          on an text/html page that may contain other HTML elements.
     *          This view is not a full view, but rather a condensed view of
     *          the contents of the MIME part. This view is intended to be
     *          displayed to the user with the intention that this MIME part's
     *          subparts may also independently be viewed inline.
     * 'raw' - The raw data of the MIME part, generally useful for downloading
     *         a part. This view exists in case this raw data needs to be
     *         altered in any way.
     * </pre>
     *
     * @return array  An array. The keys are the MIME parts that were handled
     *                by the driver. The values are either null (which
     *                indicates the driver is recommending that this
     *                particular MIME ID should not be displayed) or an array
     *                with the following keys:
     * <pre>
     * 'data' - (string) The rendered data.
     * 'status' - (array) An array of status information to be displayed to
     *            the user.  Consists of arrays with the following keys:
     *            'class' - (string) The class to use for display.
     *            'img' - (string) An image to display.
     *            'text' - (array) The text to display.
     * 'type' - (string) The MIME type of the rendered data.
     * </pre>
     */
    public function render($mode)
    {
        switch ($mode) {
        case 'full':
            try {
                return $this->_render();
            } catch (Horde_Exception $e) {
                $error = $e;
            }
            break;

        case 'inline':
            try {
                return $this->_renderInline();
            } catch (Horde_Exception $e) {
                $error = $e;
            }

        case 'info':
            try {
                return $this->_renderInfo();
            } catch (Horde_Exception $e) {
                $error = $e;
            }

        case 'raw':
            try {
                return $this->_renderRaw();
            } catch (Horde_Exception $e) {
                $error = $e;
            }
        }

        // TODO: Error handling
    }

    /**
     * Return the full HTML rendered version of the Horde_Mime_Part object.
     * This MUST be text/html data.
     *
     * @return array  See render().
     * @throws Horde_Exception
     */
    protected function _render()
    {
        $viewer = $this->_getViewer();
        return $viewer
            ? $viewer->render('full')
            : array();
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     * This MUST be text/html data.
     * This is not a full HTML document - only the HTML necessary to output
     * the part.
     *
     * @return array  See render().
     * @throws Horde_Exception
     */
    protected function _renderInline()
    {
        $viewer = $this->_getViewer();
        return $viewer
            ? $viewer->render('inline')
            : array();
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See render().
     * @throws Horde_Exception
     */
    protected function _renderInfo()
    {
        $viewer = $this->_getViewer();
        return $viewer
            ? $viewer->render('info')
            : array();
    }

    /**
     * Return the raw representation of the Horde_Mime_Part object.
     *
     * @return array  See render().
     * @throws Horde_Exception
     */
    protected function _renderRaw()
    {
        $viewer = $this->_getViewer();
        return $viewer
            ? $viewer->render('raw')
            : array();
    }

    /**
     * Can this driver render the the data?
     *
     * @param string $mode  The mode.  Either 'full', 'inline', 'info', or
     *                      'raw'.
     *
     * @return boolean  True if the driver can render the data for the given
     *                  view.
     */
    public function canRender($mode)
    {
        $viewer = $this->_getViewer();
        if ($viewer) {
            return $viewer->canRender($mode);
        }

        switch ($mode) {
        case 'full':
        case 'info':
        case 'raw':
            return $this->_capability[$mode];

        case 'inline':
            return $this->getConfigParam('inline') &&
                ($this->_metadata['forceinline'] ||
                 ($this->_capability['inline'] &&
                  ($this->_mimepart->getDisposition() != 'attachment')));

        default:
            return false;
        }
    }

    /**
     * Does this MIME part possibly contain embedded MIME parts?
     *
     * @return boolean  True if this driver supports parsing embedded MIME
     *                  parts.
     */
    public function embeddedMimeParts()
    {
        $viewer = $this->_getViewer();
        return $viewer
            ? $viewer->embeddedMimeParts()
            : $this->_metadata['embedded'];
    }

    /**
     * If this MIME part can contain embedded MIME part(s), and those part(s)
     * exist, return a representation of that data.
     *
     * @return mixed  A Horde_Mime_Part object representing the embedded data.
     *                Returns null if no embedded MIME part(s) exist.
     */
    public function getEmbeddedMimeParts()
    {
        $viewer = $this->_getViewer();
        return $viewer
            ? $viewer->getEmbeddedMimeParts()
            : $this->_getEmbeddedMimeParts();
    }

    /**
     * If this MIME part can contain embedded MIME part(s), and those part(s)
     * exist, return a representation of that data.
     *
     * @return mixed  A Horde_Mime_Part object representing the embedded data.
     *                Returns null if no embedded MIME part(s) exist.
     */
    protected function _getEmbeddedMimeParts()
    {
        return null;
    }

    /**
     * Return a configuration parameter for the current viewer.
     *
     * @param string $param  The parameter name.
     *
     * @return mixed  The value of the parameter; returns null if the
     *                parameter doesn't exist.
     */
    public function getConfigParam($param)
    {
        return isset($this->_conf[$param])
            ? $this->_conf[$param]
            : null;
    }

    /**
     * Sets a configuration parameter for the current viewer.
     *
     * @param string $param  The parameter name.
     * @param mixed $value   The parameter value.
     */
    public function setConfigParam($param, $value)
    {
        $this->_conf[$param] = $value;
    }

    /**
     * Returns the driver name for the current object.
     *
     * @return string  The driver name.
     */
    public function getDriver()
    {
        return $this->getConfigParam('_driver');
    }

    /**
     * Returns metadata information on the viewer/data.
     *
     * @param string $data  The metadata key.
     *
     * @return mixed  The requested information, or null if the key doesn't
     *                exist.
     */
    public function getMetadata($data)
    {
        return isset($this->_metadata[$data])
            ? $this->_metadata[$data]
            : null;
    }

    /**
     * Return the underlying MIME Viewer for this part.
     *
     * @return mixed  A Horde_Mime_Viewer object, or false if not found.
     */
    protected function _getViewer()
    {
        return false;
    }

    /**
     * Internal helper function to create render data array for a MIME Part
     * object that only has a single part.
     *
     * @param string $data  The rendered data.
     * @param string $type  The rendered type.
     *
     * @return array  See render().
     */
    protected function _renderReturn($data = null, $type = null)
    {
        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => (is_null($data) ? $this->_mimepart->getContents() : $data),
                'status' => array(),
                'type' => (is_null($type) ? $this->_mimepart->getType() : $type)
            )
        );
    }

    /**
     * Internal helper function to add base HTML tags to a render() return
     * array that contains a single MIME part.
     *
     * @param array $data  See render().
     *
     * @return array  See render().
     */
    protected function _renderFullReturn($data)
    {
        if (!empty($data)) {
            reset($data);
            $data[key($data)]['data'] = '<html><body>' .
                $data[key($data)]['data'] .
                '</body></html>';
        }

        return $data;
    }

    /**
     * Returns a temporary file name.
     *
     * @return string  A temp filename.
     */
    protected function _getTempFile()
    {
        return ($temp_file = $this->getConfigParam('temp_file'))
            ? call_user_func($temp_file, __CLASS__)
            : Horde_Util::getTempFile(__CLASS__);
    }

    /**
     * Filter text.
     *
     * @param string $text    TODO
     * @param mixed  $driver  TODO
     * @param array  $params  TODO
     *
     * @return string  The filtered text.
     */
    protected function _textFilter($text, $driver, array $params = array())
    {
        return ($text_filter = $this->getConfigParam('text_filter'))
            ? call_user_func($text_filter, $text, $driver, $params)
            : Horde_Text_Filter::filter($text, $driver, $params);
    }

}
