<?php
/**
 * Components_Runner_Installer:: installs a Horde component including its
 * dependencies.
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
 * Components_Runner_Installer:: installs a Horde component including its
 * dependencies.
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
class Components_Runner_Installer
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The factory for PEAR dependencies.
     *
     * @var Components_Pear_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current
     *                                         job.
     * @param Components_Pear_Factory $factory The factory for PEAR
     *                                         dependencies.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory
    ) {
        $this->_config = $config;
        $this->_factory = $factory;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $environment = realpath($options['install']);
        if (!$environment) {
            $environment = $options['install'];
        }
        $arguments = $this->_config->getArguments();
        $tree = $this->_factory
            ->createTreeHelper(
                $environment, dirname(realpath($arguments[0])), $options
            );
        $tree->getEnvironment()->provideChannel('pear.horde.org');
        $tree->installTreeInEnvironment(
            realpath($arguments[0]) . DIRECTORY_SEPARATOR . 'package.xml'
        );
    }
}
