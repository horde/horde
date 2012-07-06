<?php
/**
 * The Horde_Editor:: package provides an API to generate the code necessary
 * for embedding javascript RTE editors in a web page.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
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
     *   - browser: (Horde_Browser) A browser object.
     */
    public function __construct(Horde_Browser $browser)
    {
        $this->_browser = $browser;
    }

    /**
     * Initialize the editor.
     *
     * @param array $params  Additional parameters.
     */
    public function initialize(array $params = array())
    {
    }

    /**
     * Returns the JS code needed to instantiate the editor.
     *
     * @return array  Two keys:
     *   - files: (array) Javascript files that need to be loaded by browser.
     *   - scrips: (array) Code that needs to be run on the browser.
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
