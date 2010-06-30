<?php
/**
 * Horde_Qc_Config:: class represents configuration for the Horde quality
 * control tool.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */

/**
 * Horde_Qc_Config:: class represents configuration for the Horde quality
 * control tool.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */
class Horde_Qc_Config
{

    /**
     * The different configuration handlers.
     *
     * @var array
     */
    private $_configs;

    /**
     * Constructor.
     *
     * @param array            $parameters A list of named configuration parameters.
     * <pre>
     * 'cli' - (array)  See Horde_Qc_Config_Cli.
     * </pre>
     */
    public function __construct(
        $parameters = array()
    ) {
        if (!isset($parameters['cli'])) {
            $parameters['cli'] = array();
        }
        $this->_configs = array();
        $this->_configs[] = new Horde_Qc_Config_Cli(
            $parameters['cli']
        );
    }

    /**
     * Provide each configuration handler with the list of supported modules.
     *
     * @param Horde_Qc_Modules $modules A list of modules.
     * @return NULL
     */
    public function handleModules(Horde_Qc_Modules $modules)
    {
        foreach ($this->_configs as $config) {
            $config->handleModules($modules);
        }
    }

    /**
     * Return the options provided by the configuration hadnlers.
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

}