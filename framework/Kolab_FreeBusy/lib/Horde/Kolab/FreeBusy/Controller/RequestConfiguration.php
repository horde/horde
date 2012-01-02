<?php
/**
 * Handles the Controller setup.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Handles the Controller setup.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Controller_RequestConfiguration
implements Horde_Controller_RequestConfiguration
{
    /**
     * A fixed controller class name override.
     *
     * @var string
     */
    private $_controller;

    /**
     * A fixed settings class name override.
     *
     * @var string
     */
    private $_settings = 'Horde_Controller_SettingsExporter_Default';

    /**
     * Set a fixed override for the controller name.
     *
     * @param string $settingsName The exporter class name.
     *
     * @return NULL
     */
    public function setControllerName($controllerName)
    {
        $this->_controller = $controllerName;
    }

    /**
     * Retrieve the controller name.
     *
     * @return string The controller class name.
     */
    public function getControllerName()
    {
        return $this->_controller;
    }

    /**
     * Set the exporter name.
     *
     * @param string $settingsName The exporter class name.
     *
     * @return NULL
     */
    public function setSettingsExporterName($settingsName)
    {
        $this->_settings = $settingsName;
    }

    /**
     * Retrieve the exporter name.
     *
     * @return string The exporter class name.
     */
    public function getSettingsExporterName()
    {
        return $this->_settings;
    }
}
