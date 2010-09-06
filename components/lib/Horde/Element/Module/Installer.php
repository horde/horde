<?php
/**
 * Horde_Element_Module_Installer:: installs a Horde element including
 * its dependencies.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */

/**
 * Horde_Element_Module_Installer:: installs a Horde element including
 * its dependencies.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */
class Horde_Element_Module_Installer
implements Horde_Element_Module
{
    private $_run;

    public function getOptionGroupTitle()
    {
        return 'Installer';
    }

    public function getOptionGroupDescription()
    {
        return 'This module installs a Horde element including its dependencies.';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-i',
                '--install',
                array(
                    'action' => 'store',
                    'help'   => 'install the element into the specified absolute INSTALL location'
                )
            ),
        );
    }

    public function handle(Horde_Element_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['install'])) {
            $this->run($config);
        }
    }

    public function run(Horde_Element_Config $config)
    {
        $options = $config->getOptions();

        $pear = new PEAR();
        $pear->setErrorHandling(PEAR_ERROR_DIE);

        $pearrc = $options['install'] . DIRECTORY_SEPARATOR . '.pearrc';
        $command_config = new PEAR_Command_Config(new PEAR_Frontend_CLI(), new stdClass);
        $command_config->doConfigCreate(
            'config-create', array(), array($options['install'], $pearrc)
        );

        $pear_config = new PEAR_Config($pearrc);
        $GLOBALS['_PEAR_Config_instance'] = $pear_config;

        $channel = new PEAR_Command_Channels(
            new PEAR_Frontend_CLI(),
            $pear_config
        );
        $channel->doDiscover('channel-discover', array(), array('pear.horde.org'));
        $channel->doDiscover('channel-discover', array(), array('pear.phpunit.de'));

        $installer = new PEAR_Command_Install(
            new PEAR_Frontend_CLI(),
            $pear_config
        );

        $arguments = $config->getArguments();
        $element = basename(realpath($arguments[0]));
        $root_path = dirname(realpath($arguments[0]));

        $this->_run = array();

        $this->_installHordeDependency(
            $installer,
            $pear_config,
            $root_path,
            $element
        );
    }

    /**
     * Install a Horde dependency from the current tree (the framework).
     *
     * @param PEAR_Command_Install $installer   Installs the dependency.
     * @param PEAR_Config          $pear_config The configuration of the PEAR
     *                                          environment in which the
     *                                          dependency will be installed.
     * @param string               $root_path   Root path to the Horde framework.
     * @param string               $dependency  Package name of the dependency.
     */
    private function _installHordeDependency(
        PEAR_Command_Install $installer,
        PEAR_Config $pear_config,
        $root_path,
        $dependency
    ) {
        $package_file = $root_path . DIRECTORY_SEPARATOR
            . $dependency . DIRECTORY_SEPARATOR . 'package.xml';

        $parser = new PEAR_PackageFile_Parser_v2();
        $parser->setConfig($pear_config);
        $pkg = $parser->parse(file_get_contents($package_file), $package_file);

        $dependencies = $pkg->getDeps();
        foreach ($dependencies as $dependency) {
            if (isset($dependency['channel']) && $dependency['channel'] != 'pear.horde.org') {
                $key = $dependency['channel'] . '/' . $dependency['name'];
                if (in_array($key, $this->_run)) {
                    continue;
                }
                $installer->doInstall(
                    'install',
                    array(
                        'force' => true,
                        'channel' => $dependency['channel'],
                    ),
                    array($dependency['name'])
                );
                $this->_run[] = $key;
            } else if (isset($dependency['channel'])) {
                $key = $dependency['channel'] . '/' . $dependency['name'];
                if (in_array($key, $this->_run)) {
                    continue;
                }
                $this->_run[] = $key;
                $this->_installHordeDependency(
                    $installer,
                    $pear_config,
                    $root_path,
                    $dependency['name']
                );
            }
        }
        if (in_array($package_file, $this->_run)) {
            return;
        }
        $installer->doInstall(
            'install',
            array('nodeps' => true),
            array($package_file)
        );
        $this->_run[] = $package_file;
    }
}
