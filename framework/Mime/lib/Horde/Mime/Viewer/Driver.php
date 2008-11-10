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
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_canrender = array(
        'full' => false,
        'info' => false,
        'inline' => false,
    );

    /**
     * Constructor.
     *
     * @param array $conf  Configuration specific to the driver.
     */
    function __construct($conf = array())
    {
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
     * @param string $mode  The mode.  Either 'full', 'inline', or 'info'.
     *
     * @return array  An array with the following elements:
     * <pre>
     * 'data' - (string)
     * 'status' - (array)
     * 'type' - (string)
     * </pre>
     */
    public function render($mode)
    {
        if (is_null($this->_mimepart) || !$this->canDisplay($mode)) {
            $default = array('data' => '', 'status' => array(), 'type' => null);
            if ($mode == 'full') {
                $default['type'] => 'text/plain';
            }
            return $default;
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
     * Return the rendered version of the Horde_Mime_Part object.
     *
     * @return string  Rendered version of the Horde_Mime_Part object.
     */
    protected function _render()
    {
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return string  Rendered version of the Horde_Mime_Part object.
     */
    protected function _renderInline()
    {
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return string  Rendered information on the Horde_Mime_Part object.
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
            return $this->_canrender[$mode];

        case 'inline':
            return $this->getConfigParam('inline') && $this->_canrender['inline'];

        default:
            return false;
        }
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
}
