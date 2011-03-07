<?php
/**
 * @package Form
 */

/**
 * The Horde_Form_Renderer class provides HTML and other renderings of
 * forms for the Horde_Form:: package.
 *
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2005-2007 Matt Warden <mwarden@gmail.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Matt Warden <mwarden@gmail.com>
 * @package Form
 */
abstract class Horde_Form_Renderer {

    var $_name;
    var $_requiredLegend = false;
    var $_helpMarker = '?';
    var $_onLoadJS = array();
    var $_showHeader = true;
    var $_cols = 2;
    var $_varRenderer = null;
    var $_firstField = null;
    var $_stripedRows = true;

    protected $_submit = array();
    protected $_reset = false;

    /**
     * Does the title of the form contain HTML? If so, you are responsible for
     * doing any needed escaping/sanitization yourself. Otherwise the title
     * will be run through htmlspecialchars() before being output.
     *
     * @var boolean
     */
    var $_encodeTitle = true;

    /**
     * Construct a new Horde_Form_Renderer::.
     *
     * @param array $params  This is a hash of renderer-specific parameters.
     *                       Possible keys:
     *                       - 'encode_title': @see $_encodeTitle
     */
    function __construct($params = array())
    {
        if (isset($params['encode_title'])) {
            $this->encodeTitle($params['encode_title']);
        }

        $this->_varRenderer = new Horde_Form_VarRenderer_Xhtml;
    }

    abstract public function renderActive($form, $action, $method = 'get', $enctype = null, $focus = true);

    public function setButtons($submit, $reset = false)
    {
        if ($submit === true || is_null($submit) || empty($submit)) {
            /* Default to 'Submit'. */
            $submit = array(Horde_Model_Translation::t("Submit"));
        } elseif (!is_array($submit)) {
            /* Default to array if not passed. */
            $submit = array($submit);
        }
        /* Only if $reset is strictly true insert default 'Reset'. */
        if ($reset === true) {
            $reset = Horde_Model_Translation::t("Reset");
        }

        $this->_submit = $submit;
        $this->_reset = $reset;

        return $this;
    }

    public function addButtons($buttons)
    {
        if (!is_array($buttons)) {
            $buttons = array($buttons);
        }

        $this->_submit = array_merge($this->_submit, $buttons);
    }

    public function showHeader($bool)
    {
        $this->_showHeader = $bool;
    }

    /**
     * Sets or returns whether the form title should be encoded with
     * htmlspecialchars().
     *
     * @param boolean $encode  If true, the form title gets encoded.  If false
     *                         the title can contain HTML, but the class user
     *                         is responsible to encode any special characters.
     *
     * @return boolean  Whether the form title should be encoded.
     */
    function encodeTitle($encode = null)
    {
        if (!is_null($encode)) {
            $this->_encodeTitle = $encode;
        }
        return $this->_encodeTitle = $encode;
    }

}
