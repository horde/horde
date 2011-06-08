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
     * Constructor.
     *
     * @param string                  $directory Path to the source directory.
     * @param boolean                 $shift     Did identification of the
     *                                           component consume an argument?
     * @param Components_Config       $config    The configuration for the
     *                                           current job.
     * @param Components_Pear_Factory $factory   Generator for all
     *                                           required PEAR components.
     */
    public function __construct(
        $directory,
        $shift,
        Components_Config $config,
        Components_Pear_Factory $factory
    )
    {
        $this->_directory = $directory;
        parent::__construct($shift, $config, $factory);
    }

    /**
     * Return the name of the component.
     *
     * @return string The component name.
     */
    public function getName()
    {
        return $this->getPackage()->getName();
    }

    /**
     * Return the version of the component.
     *
     * @return string The component version.
     */
    public function getVersion()
    {
        return $this->getPackage()->getVersion();
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