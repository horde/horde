<?php
/**
 * Components_Runner_Installer:: installs a Horde component including its
 * dependencies.
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
 * Components_Runner_Installer:: installs a Horde component including its
 * dependencies.
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
class Components_Runner_Installer
{
    private $_run;

    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The factory for PEAR handlers.
     *
     * @var Components_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current
     *                                         job.
     * @param Components_Pear_Factory $factory Generator for all required PEAR
     *                                         components.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory
    ) {
        $this->_config = $config;
        $this->_factory = $factory;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $location = realpath($options['install']);
        if (!$location) {
            $location = $options['install'];
        }
        $environment = $this->_factory
            ->createInstallLocation($location . DIRECTORY_SEPARATOR . '.pearrc');
        $environment->setResourceDirectories($options);
        $pear_config = $environment->getPearConfig();

        $arguments = $this->_config->getArguments();
        $element = basename(realpath($arguments[0]));
        $root_path = dirname(realpath($arguments[0]));

        $this->_run = array();

        $this->_installHordeDependency(
            $environment,
            $root_path,
            $element
        );
    }

    /**
     * Install a Horde dependency from the current tree (the framework).
     *
     * @param string $root_path   Root path to the Horde framework.
     * @param string $dependency  Package name of the dependency.
     */
    private function _installHordeDependency($environment, $root_path, $dependency)
    {
        $package_file = $root_path . DIRECTORY_SEPARATOR
            . $dependency . DIRECTORY_SEPARATOR . 'package.xml';
        if (!file_exists($package_file)) {
            $package_file = $root_path . DIRECTORY_SEPARATOR . 'framework'  . DIRECTORY_SEPARATOR
                . $dependency . DIRECTORY_SEPARATOR . 'package.xml';
        }

        $pkg = $this->_factory->createPackageForEnvironment($package_file, $environment);
        $environment->provideChannels($pkg->listAllRequiredChannels());
        foreach ($pkg->listAllExternalDependencies() as $dependency) {
            $key = $dependency['channel'] . '/' . $dependency['name'];
            if (in_array($key, $this->_run)) {
                continue;
            }
            $environment->addPackageFromPackage(
                $dependency['channel'], $dependency['name']
            );
            $this->_run[] = $key;
        }
        foreach ($pkg->listAllHordeDependencies() as $dependency) {
            $key = $dependency['channel'] . '/' . $dependency['name'];
            if (in_array($key, $this->_run)) {
                continue;
            }
            $this->_run[] = $key;
            $this->_installHordeDependency($environment, $root_path, $dependency['name']);
        }
        if (in_array($package_file, $this->_run)) {
            return;
        }

        $environment->addPackageFromSource(
            $package_file
        );
        $this->_run[] = $package_file;
    }
}
