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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Runner_Installer:: installs a Horde component including its
 * dependencies.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
     * The list helper.
     *
     * @var Components_Helper_Installer
     */
    private $_installer;

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
     * @param Components_Config           $config    The configuration
     *                                               for the current job.
     * @param Components_Helper_Installer $installer The install helper.
     * @param Components_Pear_Factory     $factory   The factory for PEAR
     *                                               dependencies.
     * @param Component_Output            $output    The output handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Helper_Installer $installer,
        Components_Pear_Factory $factory,
        Components_Output $output
    ) {
        $this->_config    = $config;
        $this->_installer = $installer;
        $this->_factory = $factory;
        $this->_output = $output;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        if (!empty($options['destination'])) {
            $environment = realpath($options['destination']);
            if (!$environment) {
                $environment = $options['destination'];
            }
        } else {
            throw new Components_Exception('You MUST specify the path to the installation environment with the --destination flag!');
        }

        if (empty($options['pearrc'])) {
            $options['pearrc'] = $environment . '/pear.conf';
            $this->_output->info(
                sprintf(
                    'Undefined path to PEAR configuration file (--pearrc). Assuming %s for this installation.',
                    $options['pearrc']
                )
            );
        }

        if (empty($options['horde_dir'])) {
            $options['horde_dir'] = $environment;
            $this->_output->info(
                sprintf(
                    'Undefined path to horde web root (--horde-dir). Assuming %s for this installation.',
                    $options['horde_dir']
                )
            );
        }

        if (!empty($options['instructions'])) {
            if (!file_exists($options['instructions'])) {
                throw new Components_Exception(
                    sprintf(
                        'Instructions file "%s" is missing!',
                        $options['instructions']
                    )
                );
            }
            $lines = explode("\n", file_get_contents($options['instructions']));
            $result = array();
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (empty($trimmed) || preg_match('/^#/', $trimmed)) {
                    continue;
                }
                preg_match('/(.*):(.*)/', $trimmed, $matches);
                $id = $matches[1];
                $c_options = $matches[2];
                foreach (explode(',', $c_options) as $option) {
                    $result[trim($id)][trim($option)] = true;
                }
            }
            $options['instructions'] = $result;
        }

        $target = $this->_factory->createEnvironment(
                $environment, $options['pearrc']
        );
        $target->setResourceDirectories($options);

        //@todo: fix role handling
        $target->provideChannel('pear.horde.org', $options);
        $target->getPearConfig()->setChannels(array('pear.horde.org', true));
        $target->getPearConfig()->set('horde_dir', $options['horde_dir'], 'user', 'pear.horde.org');
        Components_Exception_Pear::catchError($target->getPearConfig()->store());
        $this->_installer->installTree(
            $target,
            $this->_config->getComponent(),
            $options
        );
    }
}
