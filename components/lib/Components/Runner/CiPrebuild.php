<?php
/**
 * Components_Runner_CiPrebuild:: prepares a continuous integration setup for a
 * component.
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
 * Components_Runner_CiPrebuild:: prepares a continuous integration setup for a
 * component.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Components_Runner_CiPrebuild
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The application configuration.
     *
     * @var Components_Config_Application
     */
    private $_config_application;

    /**
     * The factory for PEAR handlers.
     *
     * @var Components_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Components_Config              $config  The configuration for the
     *                                                current job.
     * @param Components_Config_Application  $cfgapp  The application
     *                                                configuration.
     * @param Components_Pear_Factory        $factory Generator for all
     *                                                required PEAR components.
     */
    public function __construct(
        Components_Config $config,
        Components_Config_Application $cfgapp,
        Components_Pear_Factory $factory
    ) {
        $this->_config             = $config;
        $this->_config_application = $cfgapp;
        $this->_factory            = $factory;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $templates = new Components_Helper_Templates_RecursiveDirectory(
            $this->_config_application->getTemplateDirectory(),
            $options['ciprebuild']
        );
        $templates->write(array('config' => $this->_config));
    }
}
