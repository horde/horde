<?php
/**
 * Ulaform_Action Class
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
    protected $_params = array();

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed for this action driver.
     */
    public function __construct($params)
    {
        $this->_params = $params;
    }

    /**
     * Returns a list of available action drivers.
     *
     * @return array  An array of available drivers.
     */
    static public function getDrivers()
    {
        static $drivers = array();
        if (!empty($drivers)) {
            return $drivers;
        }

        $driver_path = __DIR__ . '/Action/';
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

}
