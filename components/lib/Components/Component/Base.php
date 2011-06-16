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
     * The factory for PEAR handlers.
     *
     * @var Components_Factory
     */
    private $_factory;

    /**
     * Did identification of the component consume an argument?
     *
     * @var Components_Factory
     */
    private $_shift;

    /**
     * Constructor.
     *
     * @param boolean                 $shift   Did identification of the
     *                                         component consume an argument?
     * @param Components_Config       $config  The configuration for the current job.
     * @param Components_Pear_Factory $factory Generator for all
     *                                         required PEAR components.
     */
    public function __construct(
        $shift,
        Components_Config $config,
        Components_Pear_Factory $factory
    )
    {
        $this->_shift   = $shift;
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
        $options = $this->_config->getOptions();
        if (isset($options['pearrc'])) {
            return $this->_factory->createPackageForInstallLocation(
                $this->getPackageXml(), $options['pearrc']
            );
        } else {
            return $this->_factory->createPackageForDefaultLocation(
                $this->getPackageXml()
            );
        }
    }

    /**
     * Did identification of the component consume an argument?
     *
     * @return boolean True if an argument was consumed.
     */
    public function didConsumeArgument()
    {
        return $this->_shift;
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
}