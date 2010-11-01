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
     * Create a tree helper for a specific PEAR environment..
     *
     * @param string $config_file The path to the configuration file.
     * @param string $root_path   The basic root path for Horde packages.
     * @param array  $options The application options
     *
     * @return Components_Helper_Tree The tree helper.
     */
    public function createTreeHelper($config_file, $root_path, array $options)
    {
        $environment = $this->_dependencies->createInstance('Components_Pear_InstallLocation');
        $environment->setLocation(
            dirname($config_file),
            basename($config_file)
        );
        $environment->setResourceDirectories($options);
        return new Components_Helper_Tree($this, $environment, $root_path);
    }

    /**
     * Create a tree helper for a specific PEAR environment..
     *
     * @param string $config_file The path to the configuration file.
     * @param string $root_path   The basic root path for Horde packages.
     * @param array  $options The application options
     *
     * @return Components_Helper_Tree The tree helper.
     */
    public function createSimpleTreeHelper( $root_path)
    {
        return new Components_Helper_Tree(
            $this,
            $this->_dependencies->createInstance('Components_Pear_InstallLocation'),
            $root_path
        );
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
        return Components_Exception_Pear::catchError(
            $pkg->fromPackageFile($package_xml_path, PEAR_VALIDATE_NORMAL)
        );
    }

    /**
     * Create a new PEAR Package representation.
     *
     * @param string                          $package_xml_dir Path to the parent directory of the package.xml file.
     * @param Components_Pear_InstallLocation $environment      The PEAR environment.
     *
     * @return PEAR_PackageFile
     */
    public function createPackageFile(
        $package_xml_dir
    )
    {
        $environment = $this->_dependencies->getInstance('Components_Pear_InstallLocation');
        $pkg = new PEAR_PackageFile_v2_rw();
        $pkg->setPackage('REPLACE');
        $pkg->setDescription('REPLACE');
        $pkg->setSummary('REPLACE');
        $pkg->setReleaseVersion('0.0.1');
        $pkg->setApiVersion('0.0.1');
        $pkg->setReleaseStability('alpha');
        $pkg->setApiStability('alpha');
        $pkg->setChannel('pear.horde.org');
        $pkg->addMaintainer(
            'lead',
            'chuck',
            'Chuck Hagenbuch',
            'chuck@horde.org'
        );
        $pkg->addMaintainer(
            'lead',
            'jan',
            'Jan Schneider',
            'jan@horde.org'
        );
        $pkg->setLicense('REPLACE', 'REPLACE');
        $pkg->setNotes('* Initial release.');
        $pkg->clearContents(true);
        $pkg->clearDeps();
        $pkg->setPhpDep('5.2.0');
        $pkg->setPearinstallerDep('1.9.0');
        $pkg->setPackageType('php');
        $pkg->addFile('', 'something', array('role' => 'php'));
        new PEAR_Validate();
        return Components_Exception_Pear::catchError(
            $pkg->getDefaultGenerator()
            ->toPackageFile($package_xml_dir, 0)
        );
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

        return Components_Exception_Pear::catchError(
            PEAR_PackageFileManager2::importOptions(
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
                        'bin'       => 'script',
                        'script'    => 'script',
                        'doc'       => 'doc',
                        'example'   => 'doc',
                        'js'        => 'horde',
                        'horde'     => 'horde',
                        'lib'       => 'php',
                        'migration' => 'data',
                        'scripts'   => 'data',
                        'test'      => 'test',
                    ),
                )
            )
        );
    }
}