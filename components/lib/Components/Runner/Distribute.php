<?php
/**
 * Components_Runner_Distribute:: prepares a distribution package for a
 * component.
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
 * Components_Runner_Distribute:: prepares a distribution package for a
 * component.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Components_Runner_Distribute
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
     * The factory for PEAR dependencies.
     *
     * @var Components_Pear_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Components_Config             $config  The configuration for the current job.
     * @param Components_Config_Application $cfgapp  The application
     *                                               configuration.
     * @param Components_Pear_Factory       $factory The factory for PEAR
     *                                               dependencies.
     */
    public function __construct(
        Components_Config $config,
        Components_Config_Application $cfgapp,
        Components_Pear_Factory $factory
    ) {
        $this->_config  = $config;
        $this->_config_application = $cfgapp;
        $this->_factory = $factory;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $arguments = $this->_config->getArguments();

        if (!isset($options['pearrc'])) {
            $package = $this->_factory->createPackageForDefaultLocation(
                $arguments[0] . DIRECTORY_SEPARATOR . 'package.xml'
            );
        } else {
            $package = $this->_factory->createPackageForInstallLocation(
                $arguments[0] . DIRECTORY_SEPARATOR . 'package.xml',
                $options['pearrc']
            );
        }

        $version = $package->getVersion() . 'dev' . strftime('%Y%m%d%H%M');
        $package->generateSnapshot($version, dirname($options['distribute']));

        $build_template = new Components_Helper_Templates_Prefix(
            $this->_config_application->getTemplateDirectory(),
            dirname($options['distribute']),
            'distribute_',
            basename($options['distribute'])
        );
        $build_template->write(
            array('package' => $package, 'version' => $version)
        );

    }
}
