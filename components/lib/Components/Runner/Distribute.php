<?php
/**
 * Components_Runner_Distribute:: prepares a distribution package for a
 * component.
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
 * Components_Runner_Distribute:: prepares a distribution package for a
 * component.
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
class Components_Runner_Distribute
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The package handler.
     *
     * @var Components_Pear_Package
     */
    private $_package;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current job.
     * @param Components_Pear_Package $package Package handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Package $package
    ) {
        $this->_config  = $config;
        $this->_package = $package;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $arguments = $this->_config->getArguments();
        $location = realpath($options['distribute']);

    }
}
