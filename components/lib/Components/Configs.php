<?php
/**
 * Components_Configs:: class represents configuration for the
 * Horde component tool.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Configs:: class represents configuration for the
 * Horde component tool.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Configs
extends Components_Config_Base
{
    /**
     * The different configuration handlers.
     *
     * @var array
     */
    private $_configs;

    /**
     * Have the arguments been collected?
     *
     * @var boolean
     */
    private $_collected = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_configs = array();
    }

    /**
     * Add a configuration type to the configuration handler.
     *
     * @param Components_Config $type The configuration type.
     *
     * @return NULL
     */
    public function addConfigurationType(Components_Config $type)
    {
        $this->_configs[] = $type;
    }

    /**
     * Store a configuration type at the start of the configuration stack. Any
     * options provided by the new configuration can/will be overridden by
     * configurations already present.
     *
     * @param Components_Config $type The configuration type.
     *
     * @return NULL
     */
    public function unshiftConfigurationType(Components_Config $type)
    {
        array_unshift($this->_configs, $type);
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
                    if ($option !== null) {
                        $config_options[$name] = $option;
                    }
                }
                $options = array_merge($options, $config_options);
            }
        }
        $options = array_merge($options, $this->_options);
        return $options;
    }

    /**
     * Return the arguments provided by the configuration handlers.
     *
     * @return array An array of arguments.
     */
    public function getArguments()
    {
        if (!$this->_collected) {
            foreach ($this->_configs as $config) {
                $config_arguments = $config->getArguments();
                if (!empty($config_arguments)) {
                    $this->_arguments = array_merge(
                        $this->_arguments, $config_arguments
                    );
                }
            }
            $this->_collected = true;
        }
        return $this->_arguments;
    }
}