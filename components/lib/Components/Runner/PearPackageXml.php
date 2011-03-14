<?php
/**
 * Components_Runner_PearPackageXml:: updates the package.xml of a Horde
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
 * Components_Runner_PearPackageXml:: updates the package.xml of a Horde
 * component.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Components_Runner_PearPackageXml
{
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
     * @param Components_Config       $config  The configuration for the current job.
     * @param Components_Pear_Factory $factory Generator for all
     *                                         required PEAR components.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory
    ) {
        $this->_config  = $config;
        $this->_factory = $factory;
    }

    public function run()
    {
        $arguments = $this->_config->getArguments();
        $options = $this->_config->getOptions();

        if (!file_exists($this->_config->getComponentPackageXml())) {
            $this->_factory->createPackageFile(
                $this->_config->getComponentDirectory()
            );
        }

        if (isset($options['pearrc'])) {
            $package = $this->_factory->createPackageForInstallLocation(
                $this->_config->getComponentPackageXml(), $options['pearrc']
            );
        } else {
            $package = $this->_factory->createPackageForDefaultLocation(
                $this->_config->getComponentPackageXml()
            );
        }

        if (!empty($options['updatexml'])
            || (isset($arguments[0]) && $arguments[0] == 'update')) {
            $package->updatePackageFile($options['action']);
        }

    }
}
