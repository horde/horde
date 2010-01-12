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
 * @author  Nuno Loureiro <nuno@co.sapo.pt>
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Horde_Editor
 */
class Horde_Editor
{
    /**
     * Javascript code to init the editor.
     *
     * @var string
     */
    protected $_js = '';

    /**
     * Attempts to return a concrete Horde_Editor instance based on $driver.
     *
     * @param string $driver  The type of concrete Horde_Editor subclass to
     *                       return.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Editor  The newly created concrete Horde_Editor instance,
     *                       or false on error.
     */
    static public function factory($driver, $params = null)
    {
        $driver = ucfirst(basename($driver));
        if (empty($driver) || (strcmp($driver, 'None') == 0)) {
            return new Horde_Editor();
        }

        $class = __CLASS__ . '_' . $driver;
        if (!class_exists($class)) {
            throw new Exception('Driver ' . $driver . ' not found');
        }

        if (is_null($params) && class_exists('Horde')) {
            $params = Horde::getDriverConfig('editor', $driver);
        }
        return new $class($params);
    }

    /**
     * Attempts to return a reference to a concrete Horde_Editor
     * instance based on $driver. It will only create a new instance
     * if no Horde_Editor instance with the same parameters currently
     * exists.
     *
     * This should be used if multiple cache backends (and, thus,
     * multiple Horde_Editor instances) are required.
     *
     * This method must be invoked as:
     *   $var = Horde_Editor::singleton()
     *
     * @param mixed $driver  The type of concrete Horde_Editor subclass to
     *                       return. If $driver is an array, then we will look
     *                       in $driver[0]/lib/Editor/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Editor  The concrete Horde_Editor reference, or false on
     *                       error.
     */
    public static function singleton($driver, $params = null)
    {
        static $instances = array();

        if (is_null($params) && class_exists('Horde')) {
            $params = Horde::getDriverConfig('editor', $driver);
        }

        $signature = serialize(array($driver, $params));
        if (!array_key_exists($signature, $instances)) {
            $instances[$signature] = self::factory($driver, $params);
        }

        return $instances[$signature];
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
        return true;
    }

    /**
     * List the available editors.
     * Can be called statically: Horde_Editor::availableEditors();
     *
     * @return array  List of available editors.
     */
    static public function availableEditors()
    {
        $eds = array();
        $d = dir(dirname(__FILE__) . '/Editor');
        while (false !== ($entry = $d->read())) {
            if (preg_match('/\.php$/', $entry)) {
                $eds[] = basename($entry, '.php');
            }
        }

        return $eds;
    }

}
