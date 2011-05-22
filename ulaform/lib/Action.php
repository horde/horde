<?php
/**
 * Ulaform_Action Class
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: ulaform/lib/Action.php,v 1.19 2009-01-06 18:02:21 jan Exp $
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Ulaform
 */
class Ulaform_Action {

    /**
     * A hash containing any parameters for the current action driver.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed for this action driver.
     */
    function Ulaform_Action($params)
    {
        $this->_params = $params;
    }

    /**
     * Returns a list of available action drivers.
     *
     * @return array  An array of available drivers.
     */
    function getDrivers()
    {
        static $drivers = array();
        if (!empty($drivers)) {
            return $drivers;
        }

        $driver_path = dirname(__FILE__) . '/Action/';
        $drivers = array();

        if ($driver_dir = opendir($driver_path)) {
            while (false !== ($file = readdir($driver_dir))) {
                /* Hide dot files and non .php files. */
                if (substr($file, 0, 1) != '.' && substr($file, -4) == '.php') {
                    $driver = substr($file, 0, -4);
                    $driver_info = Ulaform::getActionInfo($driver);
                    $drivers[$driver] = $driver_info['name'];
                }
            }
            closedir($driver_dir);
        }

        return $drivers;
    }

    /**
     * Return a list of fields mandatory to operate the Action specified.
     * This template method is meant to be overridden in the actual
     * Action class.
     *
     * @return array  Array of form elements specified by the form
     */
    function getMandatoryFields()
    {
        return array();
    }

    /**
     * Attempts to return a concrete Ulaform_Action instance based on $driver.
     *
     * @param string $driver  The type of concrete Ulaform_Action subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Ulaform_Action  The newly created concrete Ulaform_Action
     *                         instance, or false on error.
     */
    function &factory($driver, $params = array())
    {
        $driver = basename($driver);
        include_once dirname(__FILE__) . '/Action/' . $driver . '.php';
        $class = 'Ulaform_Action_' . $driver;
        if (class_exists($class)) {
            $action = &new $class($params);
            return $action;
        } else {
            Horde::fatal(PEAR::raiseError(sprintf(_("No such action \"%s\" found"), $driver)), __FILE__, __LINE__);
        }
    }

    /**
     * Attempts to return a reference to a concrete Ulaform_Action instance
     * based on $driver.
     *
     * It will only create a new instance if no Ulaform_Action instance with
     * the same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Ulaform_Action::singleton()
     *
     * @param string $driver  The type of concrete Ulaform_Action subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The created concrete Ulaform_Action instance, or false on
     *                error.
     */
    function &singleton($driver, $params = array())
    {
        static $instances;

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Ulaform_Action::factory($driver, $params);
        }

        return $instances[$signature];
    }

}
