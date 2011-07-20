<?php
/**
 * Handles the Controller setup.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Handles the Controller setup.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Controller_RequestConfiguration
implements Horde_Controller_RequestConfiguration
{
    /**
     * The routes match dictionary.
     *
     * @var Horde_Kolab_FreeBusy_Controller_MatchDict
     */
    private $_match_dict;

    /**
     * The prefix for controller names.
     *
     * @var string
     */
    private $_prefix;

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
     * Constructor
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Controller_MatchDict $match_dict, $params
    )
    {
        $this->_match_dict = $match_dict;
        if (empty($params['prefix'])) {
            $this->_prefix = 'Horde_Kolab_FreeBusy_Controller_';
        } else {
            $this->_prefix = $params['prefix'];
        }
    }

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
        if ($this->_controller !== null) {
            return $this->_controller;
        }
        $match = $this->_match_dict->getMatchDict();
        if (empty($match['controller']) ||
            !class_exists($this->_prefix . ucfirst($match['controller']))) {
            return 'Horde_Kolab_FreeBusy_Controller_NotFound';
        } else {
            return $this->_prefix . ucfirst($match['controller']);
        }
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
