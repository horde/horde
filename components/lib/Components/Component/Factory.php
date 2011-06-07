<?php
/**
 * Components_Component_Factory:: generates component instances.
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
 * Components_Component_Factory:: generates component instances.
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
     * @param boolean $shift     Did identification of the component
     *                           consume an argument?
     *
     * @return Components_Component_Source The source component.
     */
    public function createSource($directory, $shift = true)
    {
        return new Components_Component_Source(
            $directory,
            $shift,
            $this->_config,
            $this->_factory
        );
    }

    /**
     * Create a representation for a remote component.
     *
     * @param string            $name   The name of the component.
     * @param Horde_Pear_Remote $remote The remote server handler.
     *
     * @return Components_Component_Remote The remote component.
     */
    public function createRemote($name, Horde_Pear_Remote $remote)
    {
        foreach (array('stable', 'beta', 'devel') as $stability) {
            if ($remote->getLatestRelease($name, $stability)) {
                break;
            }
        }
        return new Components_Component_Remote(
            $name,
            $remote->getLatestRelease($name, $stability),
            $remote->getLatestDownloadUri($name, $stability),
            $this->_client,
            true,
            $this->_config,
            $this->_factory
        );
    }
}