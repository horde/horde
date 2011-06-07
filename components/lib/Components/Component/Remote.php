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
     * Component name.
     *
     * @var string
     */
    private $_name;

    /**
     * Component version.
     *
     * @var string
     */
    private $_version;

    /**
     * Download location for the component.
     *
     * @var string
     */
    private $_uri;

    /**
     * The HTTP client for remote access.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * Constructor.
     *
     * @param string                  $name      Component name.
     * @param string                  $version   Component version.
     * @param string                  $uri       Download location.
     * @param Horde_Http_Client       $client    The HTTP client for remote
     *                                           access.
     * @param boolean                 $shift     Did identification of the
     *                                           component consume an argument?
     * @param Components_Config       $config    The configuration for the
     *                                           current job.
     * @param Components_Pear_Factory $factory   Generator for all
     *                                           required PEAR components.
     */
    public function __construct(
        $name,
        $version,
        $uri,
        Horde_Http_Client $client,
        $shift,
        Components_Config $config,
        Components_Pear_Factory $factory
    )
    {
        $this->_name = $name;
        $this->_version = $version;
        $this->_uri = $uri;
        $this->_client  = $client;
        parent::__construct($shift, $config, $factory);
    }

    /**
     * Return the name of the component.
     *
     * @return string The component name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the version of the component.
     *
     * @return string The component version.
     */
    public function getVersion()
    {
        return $this->_version;
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
     * Return the (base) name of the component archive.
     *
     * @return string The name of the component archive.
     */
    public function getArchiveName()
    {
        return basename($this->_uri);
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
     * Place the component source archive at the specified location.
     *
     * @param string $destination The path to write the archive to.
     *
     * @return NULL
     */
    public function placeArchive($destination)
    {
        $this->createDestination($destination);
        $this->_client->{'request.timeout'} = 60;
        file_put_contents(
            $destination . '/' . basename($this->_uri),
            $this->_client->get($this->_uri)->getStream()
        );
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