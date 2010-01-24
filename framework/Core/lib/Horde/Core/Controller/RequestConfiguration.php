<?php
/**
 * Object to contain request information as it relates to which controller to
 * create.
 *
 * @category Horde
 * @package  Horde_Core
 */
class Horde_Core_Controller_RequestConfiguration implements Horde_Controller_RequestConfiguration
{
    /**
     */
    protected $_classNames = array();

    /**
     */
    protected $_application;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_classNames = array(
            'controller' => 'Horde_Core_Controller_NotFound',
            'settings'   => 'Horde_Controller_SettingsExporter_Default',
        );
    }

    /**
     */
    public function setApplication($application)
    {
        $this->_application = $application;
    }

    /**
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     */
    public function setControllerName($controllerName)
    {
        $this->_classNames['controller'] = $controllerName;
    }

    /**
     */
    public function getControllerName()
    {
        return $this->_classNames['controller'];
    }

    /**
     */
    public function setSettingsExporterName($settingsName)
    {
        $this->_classNames['settings'] = $settingsName;
    }

    /**
     */
    public function getSettingsExporterName()
    {
        return $this->_classNames['settings'];
    }
}
