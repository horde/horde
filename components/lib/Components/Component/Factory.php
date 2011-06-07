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
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current job.
     * @param Components_Pear_Factory $factory Generator for all
     *                                         required PEAR components.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory
    )
    {
        $this->_config  = $config;
        $this->_factory = $factory;
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
     * @param string $uri The download URI of the component.
     *
     * @return Components_Component_Remote The remote component.
     */
    public function createRemote($uri)
    {
        return new Components_Component_Remote(
            $uri,
            true,
            $this->_config,
            $this->_factory
        );
    }
}