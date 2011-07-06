<?php
/**
 * Represents a source component.
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
 * Represents a source component.
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
class Components_Component_Source extends Components_Component_Base
{
    /**
     * Path to the source directory.
     *
     * @var string
     */
    private $_directory;

    /**
     * The package file representing the component.
     *
     * @var Horde_Pear_Package_Xml
     */
    private $_package;

    /**
     * The PEAR package file representing the component.
     *
     * @var PEAR_PackageFile
     */
    private $_package_file;

    /**
     * Constructor.
     *
     * @param string                  $directory Path to the source directory.
     * @param boolean                 $shift     Did identification of the
     *                                           component consume an argument?
     * @param Components_Config       $config    The configuration for the
     *                                           current job.
     * @param Components_Component_Factory $factory Generator for additional
     *                                              helpers.
     */
    public function __construct(
        $directory,
        Components_Config $config,
        Components_Component_Factory $factory
    )
    {
        $this->_directory = $directory;
        parent::__construct($config, $factory);
    }

    /**
     * Return the name of the component.
     *
     * @return string The component name.
     */
    public function getName()
    {
        return $this->_getPackageXml()->getName();
    }

    /**
     * Return the version of the component.
     *
     * @return string The component version.
     */
    public function getVersion()
    {
        return $this->_getPackageXml()->getVersion();
    }

    /**
     * Return the channel of the component.
     *
     * @return string The component channel.
     */
    public function getChannel()
    {
        return $this->_getPackageXml()->getChannel();
    }

    /**
     * Return the dependencies for the component.
     *
     * @return array The component dependencies.
     */
    public function getDependencies()
    {
        return $this->_getPackageXml()->getDependencies();
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
        $package_xml = $this->_getPackageXml();
        $package_xml->updateContents(null, $options);
        switch($action) {
        case 'print':
            return (string) $package_xml;
        case 'diff':
            $new = (string) $package_xml;
            $old = file_get_contents($this->getPackageXml());
            $renderer = new Horde_Text_Diff_Renderer_Unified();
            return $renderer->render(
                new Horde_Text_Diff(
                    'auto', array(explode("\n", $old), explode("\n", $new))
                )
            );
        default:
            file_put_contents($this->getPackageXml(), (string) $package_xml);
            return true;
        }
    }

    /**
     * Return a PEAR package representation for the component.
     *
     * @return Horde_Pear_Package_Xml The package representation.
     */
    public function _getPackageXml()
    {
        if (!isset($this->_package)) {
            $this->_package = $this->getFactory()->createPackageXml(
                $this->getPackageXml()
            );
        }
        return $this->_package;
    }

    /**
     * Return a PEAR PackageFile representation for the component.
     *
     * @return PEAR_PackageFile The package representation.
     */
    private function _getPackageFile()
    {
        if (!isset($this->_package_file)) {
            $options = $this->getOptions();
            if (isset($options['pearrc'])) {
                $this->_package_file = $this->getFactory()->pear()
                    ->createPackageForPearConfig(
                        $this->getPackageXml(), $options['pearrc']
                    );
            } else {
                $this->_package_file = $this->getFactory()->pear()
                    ->createPackageForDefaultLocation(
                        $this->getPackageXml()
                    );
            }
        }
        return $this->_package_file;
    }





    /**
     * Return the path to the local source directory.
     *
     * @return string The directory that contains the source code.
     */
    public function getPath()
    {
        return $this->_directory;
    }

    /**
     * Return the (base) name of the component archive.
     *
     * @return string The name of the component archive.
     */
    public function getArchiveName()
    {
    }

    /**
     * Return the path to the package.xml file of the component.
     *
     * @return string The path to the package.xml file.
     */
    public function getPackageXml()
    {
        return realpath($this->_directory . '/package.xml');
    }

    /**
     * Place the component source archive at the specified location.
     *
     * @param string $destination The path to write the archive to.
     *
     * @return NULL
     */
    public function placeArchive($destination)
    {
        $this->createDestination($destination);
        $version = preg_replace(
            '/([.0-9]+).*/',
            '\1dev' . strftime('%Y%m%d%H%M'),
            $this->getVersion()
        );
        $package = $this->getPackageFile();
        $package->generateSnapshot($version, $destination);
    }

    /**
     * Validate that there is a package.xml file in the source directory.
     *
     * @return NULL
     */
    public function requirePackageXml()
    {
        if (!file_exists($this->_directory . '/package.xml')) {
            throw new Components_Exception(sprintf('There is no package.xml at %s!', $this->_directory));
        }
    }

    /**
     * Bail out if this is no local source.
     *
     * @return NULL
     */
    public function requireLocal()
    {
    }
}