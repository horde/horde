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
     * Return the name of the component.
     *
     * @return string The component name.
     */
    public function getName()
    {
        return $this->getPackageXml()->getName();
    }

    /**
     * Return the component summary.
     *
     * @return string The summary of the component.
     */
    public function getSummary()
    {
        return $this->getPackageXml()->getSummary();
    }

    /**
     * Return the component description.
     *
     * @return string The description of the component.
     */
    public function getDescription()
    {
        return $this->getPackageXml()->getDescription();
    }

    /**
     * Return the version of the component.
     *
     * @return string The component version.
     */
    public function getVersion()
    {
        return $this->getPackageXml()->getVersion();
    }

    /**
     * Return the channel of the component.
     *
     * @return string The component channel.
     */
    public function getChannel()
    {
        return $this->getPackageXml()->getChannel();
    }

    /**
     * Return the dependencies for the component.
     *
     * @return array The component dependencies.
     */
    public function getDependencies()
    {
        return $this->getPackageXml()->getDependencies();
    }

    /**
     * Return the package lead developers.
     *
     * @return string The package lead developers.
     */
    public function getLeads()
    {
        return $this->getPackageXml()->getLeads();
    }

    /**
     * Return the component license.
     *
     * @return string The component license.
     */
    public function getLicense()
    {
        return $this->getPackageXml()->getLicense();
    }

    /**
     * Return the component license URI.
     *
     * @return string The component license URI.
     */
    public function getLicenseLocation()
    {
        return $this->getPackageXml()->getLicenseLocation();
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

    /**
     * Update the package.xml file for this component.
     *
     * @param string $action  The action to perform. Either "update", "diff",
     *                        or "print".
     * @param array  $options Options for this operation.
     *
     * @return NULL
     */
    public function updatePackageXml($action, $options)
    {
        throw new Components_Exception(
            'Updating the package.xml is not supported!'
        );
    }

    /**
     * Update the component changelog.
     *
     * @param string                      $log     The log entry.
     * @param Components_Helper_ChangeLog $helper  The change log helper.
     * @param array                       $options Options for the operation.
     *
     * @return NULL
     */
    public function changed(
        $log, Components_Helper_ChangeLog $helper, $options
    )
    {
        throw new Components_Exception(
            'Updating the change log is not supported!'
        );
    }

    /**
     * Identify the repository root.
     *
     * @param Components_Helper_Root $helper The root helper.
     *
     * @return NULL
     */
    public function repositoryRoot(Components_Helper_Root $helper)
    {
        throw new Components_Exception(
            'Identifying the repository root is not supported!'
        );
    }

    /**
     * Return the application options.
     *
     * @return array The options.
     */
    protected function getOptions()
    {
        return $this->_config->getOptions();
    }

    /**
     * Return the factory.
     *
     * @return Components_Component_Factory The factory.
     */
    protected function getFactory()
    {
        return $this->_factory;
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
     * Return a PEAR package representation for the component.
     *
     * @return Horde_Pear_Package_Xml The package representation.
     */
    protected function getPackageXml()
    {
        throw new Component_Exception('Not supported!');
    }

}