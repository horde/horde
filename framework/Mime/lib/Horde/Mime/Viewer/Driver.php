<?php
/**
 * The Horde_Mime_Viewer_Driver:: class provides the API for specific viewer
 * drivers to extend.
 *
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class Horde_Mime_Viewer_Driver
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
     * Viewer parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * This driver's capabilities.
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'full' => false,
        'info' => false,
        'inline' => false
    );

    /**
     * Constructor.
     *
     * @param Horde_Mime_Part &$mime_part  Reference to an object with the
     *                                     information to be rendered.
     * @param array $conf                  Configuration specific to the
     *                                     driver.
     */
    function __construct(&$mime_part, $conf = array())
    {
        $this->_mimepart = $mime_part;
        $this->_conf = $conf;
    }

    /**
     * Sets the Horde_Mime_Part object for the class.
     *
     * @param Horde_Mime_Part &$mime_part  Reference to an object with the
     *                                     information to be rendered.
     */
    public function setMIMEPart(&$mime_part)
    {
        $this->_mimepart = $mime_part;
    }

    /**
     * Set parameters for use with this object.
     *
     * @param array $params  An array of params to add to the internal
     *                       params list.
     */
    public function setParams($params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Return the rendered version of the Horde_Mime_Part object.
     *
     * @param string $mode  The mode:
     * <pre>
     * 'full' - A full representation of the MIME part, for use in a view
     *          where the output to the browser can be set to the value
     *          returned in 'type'.
     * 'inline' - A representation of the MIME part that can be viewed inline
     *            on a text/html page that may contain other HTML elements.
     * 'info' - A representation of the MIME part that can be viewed inline
     *          on an text/html page that may contain other HTML elements.
     *          This view is not a full view, but rather a condensed view of
     *          the contents of the MIME part. This view is intended to be
     *          displayed to the user with the intention that this MIME part's
     *          subparts may also independently be viewed inline.
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
     *            'img' - (string) An image to display.
     *            'text' - (array) The text to display.
     *            'type' - (string) Either 'info' or 'warning'.
     * 'type' - (string) The MIME type of the rendered data.
     * </pre>
     */
    public function render($mode)
    {
        if (!$this->canRender($mode)) {
            return array();
        }

        switch ($mode) {
        case 'full':
            return $this->_render();

        case 'inline':
            return $this->_renderInline();

        case 'info':
            return $this->_renderInfo();
        }
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
    }

    /**
     * Can this driver render the the data?
     *
     * @param string $mode  The mode.  Either 'full', 'inline', or 'info'.
     *
     * @return boolean  True if the driver can render the data for the given
     *                  view.
     */
    public function canRender($mode)
    {
        switch ($mode) {
        case 'full':
        case 'info':
            return $this->_capability[$mode];

        case 'inline':
            return $this->getConfigParam('inline') && $this->_capability['inline'];

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
        return $this->_capability['embedded'];
    }

    /**
     * If this MIME part can contain embedded MIME parts, and those embedded
     * MIME parts exist, return an altered version of the Horde_Mime_Part that
     * contains the embedded MIME part information.
     *
     * @return mixed  A Horde_Mime_Part with the embedded MIME part information
     *                or null if no embedded MIME parts exist.
     */
    public function getEmbeddedMimeParts()
    {
        return $this->_embeddedMimeParts()
            ? $this->_getEmbeddedMimeParts()
            : null;
    }

    /**
     * If this MIME part can contain embedded MIME parts, and those embedded
     * MIME parts exist, return an altered version of the Horde_Mime_Part that
     * contains the embedded MIME part information.
     *
     * @return mixed  A Horde_Mime_Part with the embedded MIME part information
     *                or null if no embedded MIME parts exist.
     */
    protected function _getEmbeddedMimeParts()
    {
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
        return isset($this->_conf[$param]) ? $this->_conf[$param] : null;
    }

    /**
     * Returns the driver name for the current object.
     *
     * @return string  The driver name.
     */
    public function getDriver()
    {
        return $this->_conf['_driver'];
    }
}
