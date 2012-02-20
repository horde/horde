<?php
/**
 * Represents base functionality for a component.
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
 * Represents base functionality for a component.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
     *                                              current job.
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
     * Return the last release date of the component.
     *
     * @return string The date.
     */
    public function getDate()
    {
        return $this->getPackageXml()->getDate();
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
     * Return the stability of the release or api.
     *
     * @param string $key "release" or "api"
     *
     * @return string The stability.
     */
    public function getState($key = 'release')
    {
        return $this->getPackageXml()->getState($key);
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
     * Return the package notes.
     *
     * @return string The notes for the current release.
     */
    public function getNotes()
    {
        return $this->getPackageXml()->getNotes();
    }

    /**
     * Indicate if the component has a local package.xml.
     *
     * @return boolean True if a package.xml exists.
     */
    public function hasLocalPackageXml()
    {
        return false;
    }

    /**
     * Returns the link to the change log.
     *
     * @param Components_Helper_ChangeLog $helper  The change log helper.
     *
     * @return string|null The link to the change log.
     */
    public function getChangelog($helper)
    {
        throw new Components_Exception('Not supported!');
    }

    /**
     * Return a data array with the most relevant information about this
     * component.
     *
     * @return array Information about this component.
     */
    public function getData()
    {
        throw new Components_Exception('Not supported!');
    }

    /**
     * Return the path to the release notes.
     *
     * @return string|boolean The path to the release notes or false.
     */
    public function getReleaseNotesPath()
    {
        return false;
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
     * Return the path to a DOCS_ORIGIN file within the component.
     *
     * @return string|NULL The path name or NULL if there is no DOCS_ORIGIN file.
     */
    public function getDocumentOrigin()
    {
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
     * Timestamp the package.xml file with the current time.
     *
     * @param array $options Options for the operation.
     *
     * @return string The success message.
     */
    public function timestampAndSync($options)
    {
        throw new Components_Exception(
            'Timestamping is not supported!'
        );
    }

    /**
     * Add the next version to the package.xml.
     *
     * @param string $version           The new version number.
     * @param string $initial_note      The text for the initial note.
     * @param string $stability_api     The API stability for the next release.
     * @param string $stability_release The stability for the next release.
     * @param array $options Options for the operation.
     *
     * @return NULL
     */
    public function nextVersion(
        $version,
        $initial_note,
        $stability_api = null,
        $stability_release = null,
        $options = array()
    )
    {
        throw new Components_Exception(
            'Setting the next version is not supported!'
        );
    }

    /**
     * Replace the current sentinel.
     *
     * @param string $changes New version for the CHANGES file.
     * @param string $app     New version for the Application.php file.
     * @param array  $options Options for the operation.
     *
     * @return string The success message.
     */
    public function currentSentinel($changes, $app, $options)
    {
        throw new Components_Exception(
            'Modifying the sentinel is not supported!'
        );
    }

    /**
     * Set the next sentinel.
     *
     * @param string $changes New version for the CHANGES file.
     * @param string $app     New version for the Application.php file.
     * @param array  $options Options for the operation.
     *
     * @return string The success message.
     */
    public function nextSentinel($changes, $app, $options)
    {
        throw new Components_Exception(
            'Modifying the sentinel is not supported!'
        );
    }

    /**
     * Tag the component.
     *
     * @param string                   $tag     Tag name.
     * @param string                   $message Tag message.
     * @param Components_Helper_Commit $commit  The commit helper.
     *
     * @return NULL
     */
    public function tag($tag, $message, $commit)
    {
        throw new Components_Exception(
            'Tagging is not supported!'
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
     * Install the channel of this component in the environment.
     *
     * @param Components_Pear_Environment $env     The environment to install
     *                                             into.
     * @param array                       $options Install options.
     *
     * @return NULL
     */
    public function installChannel(
        Components_Pear_Environment $env, $options = array()
    )
    {
        $channel = $this->getChannel();
        if (!empty($channel)) {
            $env->provideChannel(
                $channel,
                $options,
                sprintf(' [required by %s]', $this->getName())
            );
        }
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

    /**
     * Derive the basic PEAR install options from the current option set.
     *
     * @param array $options The current options.
     *
     * @return array The installatin options.
     */
    protected function getBaseInstallationOptions($options)
    {
        $installation_options = array();
        $installation_options['force'] = !empty($options['force']);
        $installation_options['nodeps'] = !empty($options['nodeps']);
        return $installation_options;
    }
}