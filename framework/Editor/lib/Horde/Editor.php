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
 * @author   Michael Slusarz <slusarz@curecanti.org>
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
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of concrete subclass to return.
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Horde_Editor  The newly created concrete instance.
     * @throws Horde_Editor_Exception.
     */
    static public function factory($driver = null, $params = null)
    {
        $driver = ucfirst(basename($driver));
        if (empty($driver) || (strcmp($driver, 'None') == 0)) {
            return new Horde_Editor();
        }

        $class = __CLASS__ . '_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Editor_Exception('Driver ' . $driver . ' not found');
    }

    /**
     * Constructor.
     *
     * @param array $params  The following configuration parameters:
     * <pre>
     * 'browser' - (Horde_Browser) A browser object.
     * </pre>
     */
    public function __construct($params = array())
    {
        if (isset($params['browser'])) {
            $this->_browser = $params['browser'];
        }
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

    /**
     * List the available editors.
     *
     * @return array  List of available editors.
     */
    static public function availableEditors()
    {
        $eds = array();

        foreach (glob(dirname(__FILE__) . '/Editor/*.php') as $val) {
            $eds[] = basename($val, '.php');
        }

        return $eds;
    }

}
