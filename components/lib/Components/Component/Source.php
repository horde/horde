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
     * Return the path to the local source directory.
     *
     * @return string The directory that contains the source code.
     */
    public function getPath()
    {
        return $this->_directory;
    }

    /**
     * Return the path to the package.xml file of the component.
     *
     * @return string The path to the package.xml file.
     */
    public function getPackageXml()
    {
        return $this->_directory . '/package.xml';
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