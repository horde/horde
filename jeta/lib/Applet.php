<?php
/**
 * Jeta_Applet:: defines an API to interact with different java applets.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL).  If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Jeta
 */
abstract class Jeta_Applet
{
    /**
     * Parameters used by the class.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Attempts to return a concrete Jeta_Applet instance based on $driver.
     *
     * @param string $driver  The type of concrete Jeta_Applet subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete Jeta_Applet instance, or
     *                false on error.
     */
    static public function factory($driver, $params = array())
    {
        $class = 'Jeta_Applet_' . basename($driver);
        return class_exists($class)
            ? new $class($params)
            : false;
    }

    /**
     * Constructor.
     */
    public function __construct($params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Generate the HTML code used to load the applet.
     *
     * @return string  The HTML needed to load the applet.
     */
    abstract public function generateAppletCode();

    /**
     * Generate the HTML param tags.
     *
     * @param array $params  An array of parameter names and values.
     *
     * @return string  The parameters in HTML code.
     */
    protected function _generateParamTags($params = array())
    {
        $out = '';
        foreach ($params as $key => $val) {
            $out .= '<param name="' . $key . '" value="' . $val . '" />';
        }
        return $out;
    }

}
