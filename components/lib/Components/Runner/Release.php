<?php
/**
 * Components_Runner_Release:: releases a new version for a package.
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
 * Components_Runner_Release:: releases a new version for a package.
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
class Components_Runner_Release
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

        $package_xml = $this->_config->getComponentPackageXml();
        if (!isset($options['pearrc'])) {
            $package = $this->_factory->createPackageForDefaultLocation(
                $package_xml
            );
        } else {
            $package = $this->_factory->createPackageForInstallLocation(
                $package_xml,
                $options['pearrc']
            );
        }

        if ($this->_doTask('timestamp')) {
            $package->timestamp();
        }

        if ($this->_doTask('package')) {
            $path = $package->generateRelease();
            if ($this->_doTask('upload')) {
                print system('scp ' . $path . ' ' . $options['releaseserver'] . ':~/');
                print system('ssh '. $options['releaseserver'] . ' "pirum add ' . $options['releasedir'] . ' ~/' . basename($path) . ' && rm ' . basename($path) . '"') . "\n";
                unlink($path);
            }

            $release = strtr(basename($path), array('.tgz' => ''));

            if ($this->_doTask('commit')) {
                system('git commit -m "Released ' . $release . '." ' . $package_xml);
            }
            if ($this->_doTask('tag')) {
                system('git tag -f -m "Released ' . $release . '." ' . strtolower($release));
            }
        }

    }

    /**
     * Did the user activate the given task?
     *
     * @param string $task The task name.
     *
     * @return boolean True if the task is active.
     */
    private function _doTask($task)
    {
        $arguments = $this->_config->getArguments();
        if ((count($arguments) == 1 && $arguments[0] == 'release')
            || in_array($task, $arguments)) {
            return true;
        }
        return false;
    }
}
