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
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current
     *                                         job.
     * @param Components_Pear_Factory $factory The factory for PEAR
     *                                         dependencies.
     * @param Component_Output        $output  The output handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory,
        Components_Output $output
    ) {
        $this->_config = $config;
        $this->_factory = $factory;
        $this->_output = $output;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $environment = realpath($options['install']);
        if (!$environment) {
            $environment = $options['install'];
        }
        if (empty($options['horde_dir'])) {
            $options['horde_dir'] = dirname($environment) . DIRECTORY_SEPARATOR . 'horde';
        }
        $arguments = $this->_config->getArguments();
        $tree = $this->_factory
            ->createTreeHelper(
                $environment, realpath($arguments[0]), $options
            );
        $tree->getEnvironment()->provideChannel('pear.horde.org');
        $tree->getEnvironment()->getPearConfig()->setChannels(array('pear.horde.org', true));
        $tree->getEnvironment()->getPearConfig()->set('horde_dir', $options['horde_dir'], 'user', 'pear.horde.org');
        Components_Exception_Pear::catchError($tree->getEnvironment()->getPearConfig()->store());
        $tree->installTreeInEnvironment(
            realpath($arguments[0]) . DIRECTORY_SEPARATOR . 'package.xml',
            $this->_output,
            $options
        );
    }
}
