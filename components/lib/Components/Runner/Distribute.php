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
     * The application configuration.
     *
     * @var Components_Config_Application
     */
    private $_config_application;

    /**
     * The factory for PEAR dependencies.
     *
     * @var Components_Pear_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Components_Config             $config  The configuration for the current job.
     * @param Components_Config_Application $cfgapp  The application
     *                                               configuration.
     * @param Components_Pear_Factory       $factory The factory for PEAR
     *                                               dependencies.
     */
    public function __construct(
        Components_Config $config,
        Components_Config_Application $cfgapp,
        Components_Pear_Factory $factory
    ) {
        $this->_config  = $config;
        $this->_config_application = $cfgapp;
        $this->_factory = $factory;
    }

    public function run()
    {
        $options = $this->_config->getOptions();
        $arguments = $this->_config->getArguments();
        $location = realpath($options['distribute']);

        $template = null;
        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->_config_application->getTemplateDirectory()
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            )
            as $file
        ) {
            if (strpos($file->getBasename(), 'distribute_') === 0) {
                $template = $file;
                break;
            }
        }
        if (empty($template)) {
            throw new Components_Exception(
                sprintf(
                    'No packaging template starting with "distribute_" was found in the template directoy %s!',
                    $this->_config_application->getTemplateDirectory()
                )
            );
        }

        ob_start();
        include $template->getPathname();
        $packaging = ob_get_clean();

        file_put_contents(
            realpath($options['distribute']) . DIRECTORY_SEPARATOR
            . substr($template->getBasename(), 11),
            $packaging
        );
    }
}
