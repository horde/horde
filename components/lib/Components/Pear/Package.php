<?php
/**
 * Components_Pear_Package:: provides package handling mechanisms.
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
 * Components_Pear_Package:: provides package handling mechanisms.
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
class Components_Pear_Package
{
    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * The PEAR environment for the package.
     *
     * @param Components_Pear_InstallLocation
     */
    private $_environment;

    /**
     * The package representation.
     *
     * @param PEAR_PackageFile_v2
     */
    private $_package_file;

    /**
     * Constructor.
     *
     * @param Component_Output $output The output handler.
     */
    public function __construct(Components_Output $output)
    {
        $this->_output = $output;
    }

    /**
     * Define the surrounding PEAR environment for the package.
     *
     * @param Components_Pear_InstallLocation
     *
     * @return NULL
     */
    public function setEnvironment(Components_Pear_InstallLocation $environment)
    {
        $this->_environment = $environment;
    }

    /**
     * Return the PEAR environment for this package.
     *
     * @return Components_Pear_InstallLocation
     */
    public function getEnvironment()
    {
        if ($this->_environment === null) {
            throw new Component_Exception('You need to set the environment first!');
        }
        return $this->_environment;
    }

    /**
     * Define the package to work on.
     *
     * @param string $package_xml_patch Path to the package.xml file.
     *
     * @return NULL
     */
    public function setPackage($package_xml_path)
    {
        $config = $this->getEnvironment()->getPearConfig();
        $pkg = new PEAR_PackageFile($config);
        $this->_package_file = $pkg->fromPackageFile($package_xml_path, PEAR_VALIDATE_NORMAL);
        if ($this->_package_file instanceOf PEAR_Error) {
            throw new Components_Exception($this->_package_file->getMessage());
        }
    }

    /**
     * Return the PEAR Package representation.
     *
     * @return PEAR_PackageFile
     */
    public function getPackageFile()
    {
        if ($this->_environment === null || $this->_package_file === null) {
            throw new Components_Exception('You need to set the environment and the path to the package file first!');
        }
        return $this->_package_file;
    }

}
