<?php
/**
 * Components_Configs:: class represents configuration for the
 * Horde element tool.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Configs:: class represents configuration for the
 * Horde element tool.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Configs
implements Components_Config
{

    /**
     * The different configuration handlers.
     *
     * @var array
     */
    private $_configs;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->_configs = array();
    }

    /**
     * Add a configuration type to the configuration handler.
     *
     * @param Components_Config $type The configuration type.
     *
     * @return NULL
     */
    public function addConfigurationType(Components_Config $type) {
        $this->_configs[] = $type;
    }

    /**
     * Provide each configuration handler with the list of supported modules.
     *
     * @param Components_Modules $modules A list of modules.
     * @return NULL
     */
    public function handleModules(Components_Modules $modules)
    {
        foreach ($this->_configs as $config) {
            $config->handleModules($modules);
        }
    }

    /**
     * Return the options provided by the configuration handlers.
     *
     * @return array An array of options.
     */
    public function getOptions()
    {
        $options = array();
        foreach ($this->_configs as $config) {
            if (count($config->getOptions()) !== 0) {
                $config_options = array();
                foreach ($config->getOptions() as $name => $option) {
                    $config_options[$name] = $option;
                }
                $options = array_merge($options, $config_options);
            }
        }
        return $options;
    }

    /**
     * Return the arguments provided by the configuration handlers.
     *
     * @return array An array of arguments.
     */
    public function getArguments()
    {
        $arguments = array();
        foreach ($this->_configs as $config) {
            $config_arguments = $config->getArguments();
            if (!empty($config_arguments)) {
                $arguments = array_merge($arguments, $config_arguments);
            }
        }
        return $arguments;
    }
}