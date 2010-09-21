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
     * The install location.
     *
     * @var Components_Pear_InstallLocation
     */
    private $_location;

    /**
     * Constructor.
     *
     * @param Components_Config $config The configuration for the current job.
     * @param Components_Pear_InstallLocation $location Represents the install
     *                                                  location and its
     *                                                  corresponding configuration.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_InstallLocation $location
    ) {
        $this->_config = $config;
        $this->_location = $location;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $location = realpath($options['install']);
        if (!$location) {
            $location = $options['install'];
        }
        $this->_location->setLocation($location, '.pearrc');
        $pear_config = $this->_location->getPearConfig();
        if (!empty($options['channelxmlpath'])) {
            $this->_location->setChannelDirectory($options['channelxmlpath']);
        } else if (!empty($options['sourcepath'])) {
            $this->_location->setChannelDirectory($options['sourcepath']);
        }
        if (!empty($options['sourcepath'])) {
            $this->_location->setSourceDirectory($options['sourcepath']);
        }

        $arguments = $this->_config->getArguments();
        $element = basename(realpath($arguments[0]));
        $root_path = dirname(realpath($arguments[0]));

        $this->_run = array();

        $this->_installHordeDependency(
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
    private function _installHordeDependency($root_path, $dependency)
    {
        $package_file = $root_path . DIRECTORY_SEPARATOR
            . $dependency . DIRECTORY_SEPARATOR . 'package.xml';
        if (!file_exists($package_file)) {
            $package_file = $root_path . DIRECTORY_SEPARATOR . 'framework'  . DIRECTORY_SEPARATOR
                . $dependency . DIRECTORY_SEPARATOR . 'package.xml';
        }

        $parser = new PEAR_PackageFile_Parser_v2();
        $parser->setConfig($this->_location->getPearConfig());
        $pkg = $parser->parse(file_get_contents($package_file), $package_file);

        $dependencies = $pkg->getDeps();
        foreach ($dependencies as $dependency) {
            if (isset($dependency['channel']) && $dependency['channel'] != 'pear.horde.org') {
                $this->_location->provideChannel($dependency['channel']);
                $key = $dependency['channel'] . '/' . $dependency['name'];
                if (in_array($key, $this->_run)) {
                    continue;
                }
                $this->_location->addPackageFromPackage(
                    $dependency['channel'], $dependency['name']
                );
                $this->_run[] = $key;
            } else if (isset($dependency['channel'])) {
                $this->_location->provideChannel($dependency['channel']);
                $key = $dependency['channel'] . '/' . $dependency['name'];
                if (in_array($key, $this->_run)) {
                    continue;
                }
                $this->_run[] = $key;
                $this->_installHordeDependency($root_path, $dependency['name']);
            }
        }
        if (in_array($package_file, $this->_run)) {
            return;
        }

        $this->_location->provideChannel($pkg->getChannel());
        $this->_location->addPackageFromSource(
            $package_file
        );
        $this->_run[] = $package_file;
    }
}
