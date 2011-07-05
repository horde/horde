<?php
/**
 * Components_Module_Installer:: installs a Horde element including
 * its dependencies.
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
 * Components_Module_Installer:: installs a Horde element including
 * its dependencies.
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
class Components_Module_Installer
extends Components_Module_Base
{
    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return 'Installer';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return 'This module installs a Horde component including its dependencies.';
    }

    /**
     * Return the options for this module.
     *
     * @return array The group options.
     */
    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-i',
                '--install',
                array(
                    'action' => 'store_true',
                    'help'   => 'Install the selected element into the PEAR environment indicated with the --destination option.'
                )
            ),
            new Horde_Argv_Option(
                '-S',
                '--sourcepath',
                array(
                    'action' => 'store',
                    'help'   => 'Location of downloaded PEAR packages. Specifying this path allows you to avoid accessing the network for installing new packages.'
                )
            ),
            new Horde_Argv_Option(
                '-X',
                '--channelxmlpath',
                array(
                    'action' => 'store',
                    'help'   => 'Location of static channel XML descriptions. These files need to be named CHANNEL.channel.xml (e.g. pear.php.net.channel.xml). Specifying this path allows you to avoid accessing the network for installing new channels. If this is not specified but SOURCEPATH is given then SOURCEPATH will be checked for such channel XML files.'
                )
            ),
            new Horde_Argv_Option(
                '-I',
                '--include',
                array(
                    'action' => 'store',
                    'help'   => 'The list of optional dependencies that should be included in the installation. You can either specify packages by name (e.g. PEAR), by a combination of channel and name (e.g. pear.php.net/PEAR), a channel name (e.g. channel:pear.php.net), or all packages by the special keyword ALL. Several entries need to be separated by ",". The default for this option is "ALL".',
                    'default' => 'ALL',
                    'dest' => 'include',
                )
            ),
            new Horde_Argv_Option(
                '-E',
                '--exclude',
                array(
                    'action' => 'store',
                    'help'   => 'The list of optional dependencies that should be excluded during the installation. You can either specify packages by name (e.g. PEAR), by a combination of channel and name (e.g. pear.php.net/PEAR), a channel name (e.g. channel:pear.php.net), or all packages by the special keyword ALL. Several entries need to be separated by ",". The default for this option is "channel:pecl.php.net".',
                    'default' => 'channel:pecl.php.net',
                    'dest' => 'exclude',
                )
            ),
            new Horde_Argv_Option(
                '-s',
                '--symlink',
                array(
                    'action' => 'store_true',
                    'help'   => 'Symlink the files from the source repository rather than copying them to the install location. This is intended for the development mode where you want to have your edits to have a direct effect on your installation while still retaining the possibility of commiting to your repository.',
                )
            ),
            new Horde_Argv_Option(
                '-H',
                '--horde-dir',
                array(
                    'action' => 'store',
                    'help'   => 'The location of the horde installation directory. The default will be the DESTINATION/horde directory',
                )
            ),
        );
    }

    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return '  install     - Install a component.
';
    }

    /**
     * Return the action arguments supported by this module.
     *
     * @return array A list of supported action arguments.
     */
    public function getActions()
    {
        return array('install');
    }

    /**
     * Return the help text for the specified action.
     *
     * @param string $action The action.
     *
     * @return string The help text.
     */
    public function getHelp($action)
    {
        return 'This module installs the selected component (including its dependencies) into a target environment.';
    }

    /**
     * Return the options that should be explained in the context help.
     *
     * @return array A list of option help texts.
     */
    public function getContextOptionHelp()
    {
        return array(
            '--destination' => 'The path to the target for the installation.',
            '--sourcepath' => '',
            '--channelxmlpath' => '',
            '--exclude' => '',
            '--include' => '',
            '--horde-dir' => '',
            '--symlink' => '',
        );
    }

    /**
     * Determine if this module should act. Run all required actions if it has
     * been instructed to do so.
     *
     * @param Components_Config $config The configuration.
     *
     * @return boolean True if the module performed some action.
     */
    public function handle(Components_Config $config)
    {
        $options = $config->getOptions();
        $arguments = $config->getArguments();
        if (!empty($options['install'])
            || (isset($arguments[0]) && $arguments[0] == 'install')) {
            $config->getComponent()->requirePackageXml();
            $this->_dependencies->getRunnerInstaller()->run();
            return true;
        }
    }
}
