<?php
/**
 * Components_Pear_Factory:: generates PEAR specific handlers.
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
 * Components_Pear_Factory:: generates PEAR specific handlers.
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
class Components_Pear_Factory
{
    /**
     * The dependency broker.
     *
     * @param Component_Dependencies
     */
    private $_dependencies;

    /**
     * Constructor.
     *
     * @param Component_Dependencies $dependencies The dependency broker.
     */
    public function __construct(Components_Dependencies $dependencies)
    {
        $this->_dependencies = $dependencies;
    }

    /**
     * Create a representation for a PEAR environment.
     *
     * @param string $config_file The path to the configuration file.
     *
     * @return NULL
     */
    public function createInstallLocation($config_file)
    {
        $install_location = $this->_dependencies->createInstance('Components_Pear_InstallLocation');
        $install_location->setLocation(
            dirname($config_file),
            basename($config_file)
        );
        return $install_location;
    }

    /**
     * Create a package representation for a specific PEAR environment.
     *
     * @param string                          $package_file The path of the package XML file.
     * @param Components_Pear_InstallLocation $environment  The PEAR environment.
     *
     * @return NULL
     */
    public function createPackageForEnvironment(
        $package_file,
        Components_Pear_InstallLocation $environment
    ) {
        $package = $this->_dependencies->createInstance('Components_Pear_Package');
        $package->setFactory($this);
        $package->setEnvironment($environment);
        $package->setPackageXml($package_file);
        return $package;
    }

    /**
     * Create a package representation for a specific PEAR environment.
     *
     * @param string $package_file The path of the package XML file.
     * @param string $config_file  The path to the configuration file.
     *
     * @return NULL
     */
    public function createPackageForInstallLocation($package_file, $config_file)
    {
        $package = $this->_dependencies->createInstance('Components_Pear_Package');
        $package->setFactory($this);
        $package->setEnvironment($this->createInstallLocation($config_file));
        $package->setPackageXml($package_file);
        return $package;
    }

    /**
     * Create a package representation for the default PEAR environment.
     *
     * @param string $package_file The path of the package XML file.
     *
     * @return NULL
     */
    public function createPackageForDefaultLocation($package_file)
    {
        $package = $this->_dependencies->createInstance('Components_Pear_Package');
        $package->setFactory($this);
        $package->setEnvironment($this->_dependencies->getInstance('Components_Pear_InstallLocation'));
        $package->setPackageXml($package_file);
        return $package;
    }

    /**
     * Return the PEAR Package representation.
     *
     * @param string                          $package_xml_path Path to the package.xml file.
     * @param Components_Pear_InstallLocation $environment      The PEAR environment.
     *
     * @return PEAR_PackageFile
     */
    public function getPackageFile(
        $package_xml_path,
        Components_Pear_InstallLocation $environment
    )
    {
        $pkg = new PEAR_PackageFile($environment->getPearConfig());
        $package_file = $pkg->fromPackageFile($package_xml_path, PEAR_VALIDATE_NORMAL);
        if ($package_file instanceOf PEAR_Error) {
            throw new Components_Exception($package_file->getMessage());
        }
        return $package_file;
    }

    /**
     * Return a writeable PEAR Package representation.
     *
     * @param string                          $package_xml_path Path to the package.xml file.
     * @param Components_Pear_InstallLocation $environment      The PEAR environment.
     *
     * @return PEAR_PackageFileManager2
     */
    public function getPackageRwFile(
        $package_xml_path,
        Components_Pear_InstallLocation $environment
    ) {
        /**
         * Ensure we setup the PEAR_Config according to the PEAR environment
         * the user set.
         */
        $environment->getPearConfig();

        if (!class_exists('PEAR_PackageFileManager2')) {
            throw new Components_Exception(
                'The Package "PEAR_PackageFileManager2" is missing in the PEAR environment. Please install it so that you can run this action.'
            );
        }

        $package_rw_file = PEAR_PackageFileManager2::importOptions(
            $package_xml_path,
            array(
                'packagedirectory' => dirname($package_xml_path),
                'filelistgenerator' => 'file',
                'clearcontents' => false,
                'clearchangelog' => false,
                'simpleoutput' => true,
                'ignore' => array('*~', 'conf.php', 'CVS/*'),
                'include' => '*',
                'dir_roles' =>
                array(
                    'doc'       => 'doc',
                    'example'   => 'doc',
                    'js'        => 'horde',
                    'lib'       => 'php',
                    'migration' => 'data',
                    'script'    => 'script',
                    'test'      => 'test',
                ),
            )
        );

        if ($package_rw_file instanceOf PEAR_Error) {
            throw new Components_Exception($package_file->getMessage());
        }
        return $package_rw_file;
    }
}