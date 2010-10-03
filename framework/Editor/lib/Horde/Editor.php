<?php
/**
 * The Horde_Editor:: package provides an API to generate the code necessary
 * for embedding javascript RTE editors in a web page.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Nuno Loureiro <nuno@co.sapo.pt>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Editor
 */
class Horde_Editor
{
    /**
     * A browser detection object.
     *
     * @var Horde_Browser
     */
    protected $_browser;

    /**
     * Javascript code to init the editor.
     *
     * @var string
     */
    protected $_js = '';

    /**
     * Constructor.
     *
     * @param array $params  The following configuration parameters:
     * <pre>
     * 'browser' - (Horde_Browser) A browser object.
     * </pre>
     */
    public function __construct(Horde_Browser $browser)
    {
        $this->_browser = $params['browser'];
    }

    public function initialize(array $params = array())
    {
    }

    /**
     * Returns the JS code needed to instantiate the editor.
     *
     * @return string  Javascript code.
     */
    public function getJS()
    {
        return $this->_js;
    }

    /**
     * Does the current browser support the Horde_Editor driver.
     *
     * @return boolean  True if the browser supports the editor.
     */
    public function supportedByBrowser()
    {
        return false;
    }
}
