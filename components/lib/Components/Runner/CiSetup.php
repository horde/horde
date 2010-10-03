<?php
/**
 * Components_Runner_CiSetup:: prepares a continuous integration setup for a
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
 * Components_Runner_CiSetup:: prepares a continuous integration setup for a
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
class Components_Runner_CiSetup
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The package handler.
     *
     * @var Components_Pear_Package
     */
    private $_package;

    /**
     * Constructor.
     *
     * @param Components_Config               $config   The configuration for the
     *                                                  current job.
     * @param Components_Pear_InstallLocation $location Represents the install
     *                                                  location and its
     *                                                  corresponding configuration.
     * @param Components_Pear_Package         $package  Package handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_InstallLocation $location,
        Components_Pear_Package $package
    ) {
        $this->_config   = $config;
        $this->_location = $location;
        $this->_package  = $package;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $arguments = $this->_config->getArguments();
        $pkgfile = $arguments[0] . DIRECTORY_SEPARATOR . 'package.xml';
        $name = basename($arguments[0]);
        if (basename(dirname($arguments[0])) == 'framework') {
            $origin = 'framework' . DIRECTORY_SEPARATOR . $name;
        } else {
            $origin = $name;
        }
        $test_path = strtr($name, '_', '/');

        if (!isset($options['toolsdir'])) {
            $options['toolsdir'] = 'php-hudson-tools/workspace/pear/pear';
        }
        if (!isset($options['pearrc'])) {
            throw new Components_Exception(
                'You are required to set the path to a PEAR environment for this package'
            );
        }

        $this->_location->setLocation(
            dirname($options['pearrc']),
            basename($options['pearrc'])
        );
        $this->_package->setEnvironment($this->_location);
        $this->_package->setPackage($pkgfile);
        $description = $this->_package->getPackageFile()->getDescription();

        if (!empty($options['cisetup'])) {
            $in = file_get_contents(
                Components_Constants::getDataDirectory()
                . DIRECTORY_SEPARATOR . 'hudson-component-config.xml.template',
                'r'
            );
            file_put_contents(
                $options['cisetup'] . DIRECTORY_SEPARATOR . 'config.xml',
                sprintf($in, $origin, 'horde', $options['toolsdir'], $description)
            );
        }

        if (!empty($options['ciprebuild'])) {
            $in = file_get_contents(
                Components_Constants::getDataDirectory()
                . DIRECTORY_SEPARATOR . 'hudson-component-build.xml.template',
                'r'
            );
            file_put_contents(
                $options['ciprebuild'] . DIRECTORY_SEPARATOR . 'build.xml',
                sprintf($in, $options['toolsdir'])
            );
            $in = file_get_contents(
                Components_Constants::getDataDirectory()
                . DIRECTORY_SEPARATOR . 'hudson-component-phpunit.xml.template',
                'r'
            );
            file_put_contents(
                $options['ciprebuild'] . DIRECTORY_SEPARATOR . 'phpunit.xml',
                sprintf($in, $name, $test_path)
            );
        }
    }
}
