<?php
/**
 * Represents base functionality for a component.
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
 * Represents base functionality for a component.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
abstract class Components_Component_Base implements Components_Component
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The factory for additional helpers.
     *
     * @var Components_Component_Factory
     */
    private $_factory;

    /**
     * The PEAR package file representing the component.
     *
     * @var PEAR_PackageFile
     */
    private $_package;

    /**
     * Constructor.
     *
     * @param Components_Config            $config  The configuration for the
                                                    current job.
     * @param Components_Component_Factory $factory Generator for additional
     *                                              helpers.
     */
    public function __construct(
        Components_Config $config,
        Components_Component_Factory $factory
    )
    {
        $this->_config  = $config;
        $this->_factory = $factory;
    }

    /**
     * Return a PEAR package representation for the component.
     *
     * @return PEAR_PackageFile The package representation.
     */
    public function getPackage()
    {
        if (!isset($this->_package)) {
            $options = $this->_config->getOptions();
            if (isset($options['pearrc'])) {
                $this->_package = $this->_factory->pear()->createPackageForPearConfig(
                    $this->getPackageXml(), $options['pearrc']
                );
            } else {
                $this->_package = $this->_factory->pear()->createPackageForDefaultLocation(
                    $this->getPackageXml()
                );
            }
        }
        return $this->_package;
    }

    /**
     * Create the specified directory.
     *
     * @param string $destination The destination path.
     *
     * @return NULL
     */
    protected function createDestination($destination)
    {
        if (!file_exists($destination)) {
            mkdir($destination, 0700, true);
        }
    }

    /**
     * Return the dependency list for the component.
     *
     * @return Components_Component_DependencyList The dependency list.
     */
    public function getDependencyList()
    {
        return $this->_factory->createDependencyList($this);
    }
}