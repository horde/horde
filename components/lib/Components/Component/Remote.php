<?php
/**
 * Represents a remote component.
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
 * Represents a remote component.
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
class Components_Component_Remote extends Components_Component_Base
{
    /**
     * Download location for the component.
     *
     * @var string
     */
    private $_uri;

    /**
     * Constructor.
     *
     * @param string                  $uri       Download location.
     * @param Components_Config       $config    The configuration for the
     *                                           current job.
     * @param Components_Pear_Factory $factory   Generator for all
     *                                           required PEAR components.
     */
    public function __construct(
        $uri,
        Components_Config $config,
        Components_Pear_Factory $factory
    )
    {
        $this->_uri = $uri;
        parent::__construct($config, $factory);
    }

    /**
     * Return the path to the local source directory.
     *
     * @return string The directory that contains the source code.
     */
    public function getPath()
    {
    }

    /**
     * Return the path to the package.xml file of the component.
     *
     * @return string The path to the package.xml file.
     */
    public function getPackageXml()
    {
    }

    /**
     * Validate that there is a package.xml file in the source directory.
     *
     * @return NULL
     */
    public function requirePackageXml()
    {
    }

    /**
     * Bail out if this is no local source.
     *
     * @return NULL
     */
    public function requireLocal()
    {
        throw new Components_Exception(
            'This operation is not possible with a remote component!'
        );
    }
}