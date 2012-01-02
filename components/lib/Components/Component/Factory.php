<?php
/**
 * Components_Component_Factory:: generates component instances.
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
 * Components_Component_Factory:: generates component instances.
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
class Components_Component_Factory
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
     * The HTTP client for remote access.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * The first source component generated
     *
     * @var Components_Component
     */
    private $_first_source;

    /**
     * The git root path.
     *
     * @var Components_Helper_Root
     */
    private $_git_root;

    /**
     * The resolver instance.
     *
     * @var Components_Component_Resolver
     */
    private $_resolver;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current job.
     * @param Components_Pear_Factory $factory Generator for all
     *                                         required PEAR components.
     * @param Horde_Http_Client       $client  The HTTP client for remote access.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory,
        Horde_Http_Client $client
    )
    {
        $this->_config  = $config;
        $this->_factory = $factory;
        $this->_client  = $client;
    }

    /**
     * Create a representation for a source component.
     *
     * @param string  $directory The directory of the component.
     *
     * @return Components_Component_Source The source component.
     */
    public function createSource($directory)
    {
        $component = new Components_Component_Source(
            $directory,
            $this->_config,
            $this
        );
        if ($this->_first_source === null) {
            $this->_first_source = $component;
        }
        return $component;
    }

    /**
     * Create a representation for a component archive.
     *
     * @param string $archive The path to the component archive.
     *
     * @return Components_Component_Archive The archive component.
     */
    public function createArchive($archive)
    {
        $component = new Components_Component_Archive(
            $archive,
            $this->_config,
            $this
        );
        return $component;
    }

    /**
     * Create a representation for a remote component.
     *
     * @param string            $name      The name of the component.
     * @param string            $stability The stability of the component.
     * @param string            $channel   The component channel.
     * @param Horde_Pear_Remote $remote    The remote server handler.
     *
     * @return Components_Component_Remote The remote component.
     */
    public function createRemote(
        $name, $stability, $channel, Horde_Pear_Remote $remote
    )
    {
        return new Components_Component_Remote(
            $name,
            $stability,
            $channel,
            $remote,
            $this->_client,
            $this->_config,
            $this
        );
    }

    /**
     * Provide access to the PEAR helper factory.
     *
     * @return Components_Pear_Factory The PEAR factory.
     */
    public function pear()
    {
        return $this->_factory;
    }

    /**
     * Create a component dependency list.
     *
     * @param Components_Component $component The component.
     *
     * @return Components_Component_DependencyList The dependency list.
     */
    public function createDependencyList(Components_Component $component)
    {
        return new Components_Component_DependencyList($component, $this);
    }

    /**
     * Create a component dependency representation.
     *
     * @param array                $dependencies The dependency information.
     * @param Components_Component $parent       The parent component.
     *
     * @return Components_Component_Dependency The dependency.
     */
    public function createDependency($dependencies)
    {
        return new Components_Component_Dependency(
            $dependencies, $this
        );
    }

    /**
     * Get the component resolver.
     *
     * @return Components_Component_Resolver The component resolver.
     */
    public function getResolver()
    {
        if (!isset($this->_resolver)) {
            $this->_resolver = $this->createResolver();
        }
        return $this->_resolver;
    }

    /**
     * Create a component resolver.
     *
     * @return Components_Component_Resolver The component resolver.
     */
    public function createResolver()
    {
        return new Components_Component_Resolver(
            $this->getGitRoot(),
            $this
        );
    }

    /**
     * Create a remote PEAR server handler for a specific channel.
     *
     * @param string $channel The channel name.
     *
     * @return Horde_Pear_Remote The remote handler.
     */
    public function createRemoteChannel($channel)
    {
        return new Horde_Pear_Remote($channel);
    }

    /**
     * Create the sentinel helper.
     *
     * @param string $directory The directory the sentinel should act in.
     *
     * @return Horde_Release_Sentinel The sentinel helper.
     */
    public function createSentinel($directory)
    {
        return new Horde_Release_Sentinel($directory);
    }

    /**
     * Return the repository root helper.
     *
     * @return Components_Helper_Root The helper.
     */
    public function getGitRoot()
    {
        if (!isset($this->_git_root)) {
            $this->_git_root = $this->createGitRoot();
        }
        return $this->_git_root;
    }

    /**
     * Create the repository root helper.
     *
     * @return Components_Helper_Root The helper.
     */
    public function createGitRoot()
    {
        if (isset($this->_first_source)) {
            return new Components_Helper_Root(
                $this->_config->getOptions(),
                $this->_first_source
            );
        } else {
            return new Components_Helper_Root(
                $this->_config->getOptions()
            );
        }
    }

    /**
     * Return the package.xml handler.
     *
     * @param string $package_xml_path Path to the package.xml file.
     *
     * @return Horde_Pear_Package_Xml
     */
    public function createPackageXml($package_xml_path)
    {
        return new Horde_Pear_Package_Xml($package_xml_path);
    }

    /**
     * Create a new PEAR Package representation.
     *
     * @param string $package_xml_dir Path to the parent directory of the
     *                                new package.xml file.
     *
     * @return PEAR_PackageFile
     */
    public function createPackageFile($package_xml_dir)
    {
        $type = new Horde_Pear_Package_Type_Horde($package_xml_dir);
        $type->writePackageXmlDraft();
    }

    /**
     * Create a new content listing.
     *
     * @param string $package_xml_dir Path to the parent directory of the
     *                                new package.xml file.
     *
     * @return Horde_Pear_Package_Contents_List
     */
    public function createContentList($package_xml_dir)
    {
        return new Horde_Pear_Package_Contents_List(
            new Horde_Pear_Package_Type_Horde(
                $package_xml_dir,
                $this->getGitRoot()->getRoot()
            )
        );
    }
}