<?php
/**
 * The Horde_MIME_Viewer_Driver:: class provides the API for specific viewer
 * drivers to extend.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_MIME
 */
class Horde_MIME_Viewer_Driver
{
    /**
     * Viewer configuration parameters.
     *
     * @var array
     */
    protected $_conf = array();

    /**
     * The Horde_MIME_Part object to render.
     *
     * @var Horde_MIME_Part
     */
    protected $_mimepart = null;

    /**
     * The MIME display type for this part.
     *
     * @var string
     */
    protected $_type = null;

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
     * Sets the Horde_MIME_Part object for the class.
     *
     * @param Horde_MIME_Part &$mime_part  Reference to an object with the
     *                                     information to be rendered.
     */
    public function setMIMEPart(&$mime_part)
    {
        $this->_mimepart = $mime_part;
    }

    /**
     * Return the MIME type of the rendered content.  This can be
     * overridden by the individual drivers, depending on what format
     * they output in. By default, it passes through the MIME type of
     * the object, or replaces custom extension types with
     * 'text/plain' to let the browser do a best-guess render.
     *
     * @return string  MIME type of the output content.
     */
    public function getType()
    {
        if (is_null($this->_type)) {
            return is_null(is_null($this->_mimepart) || ($this->_mimepart->getPrimaryType() == 'x-extension'))
                ? 'text/plain'
                : $this->_mimepart->getType(true);
        }

        return $this->_type;
    }

    /**
     * Return the rendered version of the Horde_MIME_Part object.
     *
     * @return string  Rendered version of the Horde_MIME_Part object.
     */
    public function render()
    {
        return (is_null($this->_mimepart) || !$this->canDisplay())
            ? ''
            : $this->_render();
    }

    /**
     * Return the rendered version of the Horde_MIME_Part object.
     *
     * @return string  Rendered version of the Horde_MIME_Part object.
     */
    protected function _render()
    {
    }

    /**
     * Return the rendered inline version of the Horde_MIME_Part object.
     *
     * @return string  Rendered version of the Horde_MIME_Part object.
     */
    public function renderInline()
    {
        return (is_null($this->_mimepart) || !$this->canDisplayInline())
            ? ''
            : $this->_renderInline();
    }

    /**
     * Return the rendered inline version of the Horde_MIME_Part object.
     *
     * @return string  Rendered version of the Horde_MIME_Part object.
     */
    protected function _renderInline()
    {
    }

    /**
     * Return the rendered information about the Horde_MIME_Part object.
     *
     * @return string  Rendered information on the Horde_MIME_Part object.
     */
    public function renderInfo()
    {
        return (is_null($this->_mimepart) || !$this->canDisplayInfo())
            ? ''
            : $this->_renderInfo();
    }

    /**
     * Return the rendered information about the Horde_MIME_Part object.
     *
     * @return string  Rendered information on the Horde_MIME_Part object.
     */
    protected function _renderInfo()
    {
    }

    /**
     * Can this driver render the the data?
     *
     * @return boolean  True if the driver can render data.
     */
    public function canDisplay()
    {
        return $this->_canrender['full'];
    }

    /**
     * Can this driver render the the data inline?
     *
     * @return boolean  True if the driver can display inline.
     */
    public function canDisplayInline()
    {
        return $this->getConfigParam('inline') && $this->_canrender['inline'];
    }

    /**
     * Can this driver render the the data inline?
     *
     * @return boolean  True if the driver can display inline.
     */
    public function canDisplayInfo()
    {
        return $this->_canrender['info'];
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
