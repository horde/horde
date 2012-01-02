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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Module_Installer:: installs a Horde element including
 * its dependencies.
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
                '--nodeps',
                array(
                    'action' => 'store_true',
                    'help'   => 'Ignore package dependencies and just install the specified package.'
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
                '--build-distribution',
                array(
                    'action' => 'store_true',
                    'help'   => 'Download all elements required for installation to SOURCEPATH and CHANNELXMLPATH. If those paths have been left undefined they will be created automatically at DESTINATION/distribution if you activate this flag.',
                )
            ),
            new Horde_Argv_Option(
                '--instructions',
                array(
                    'action' => 'store',
                    'help'   => 'Points to a file that contains per-package installation instructions. This is a plain text file that holds a package identifier per line. You can either specify packages by name (e.g. PEAR), by a combination of channel and name (e.g. pear.php.net/PEAR), a channel name (e.g. channel:pear.php.net), or all packages by the special keyword ALL. The package identifier is followed by a set of options that can be any keyword of the following: include,exclude,symlink,git,snapshot,stable,beta,alpha,devel,force,nodeps.

      These have the following meaning:

       - include:  Include optional package(s) into the installation.
       - exclude:  Exclude optional package(s) from installation.
       - git:      Prefer installing from a source component.
       - snapshot: Prefer installing from a snapshot in the SOURCEPATH.
       - stable:   Prefer a remote package of stability "stable".
       - beta:     Prefer a remote package of stability "beta".
       - alpha:    Prefer a remote package of stability "alpha".
       - devel:    Prefer a remote package of stability "devel".
       - symlink:  Symlink a source component rather than copying it.
       - force:    Force the PEAR installer to install the package.
       - nodeps:   Instruct the PEAR installer to ignore dependencies.

      The INSTRUCTIONS file could look like this (ensure the identifiers move from less specific to more specific as the latter options will overwrite previous instructions in case both identifier match a compnent):

       ALL: symlink
       Horde_Test: exclude
',
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
            '--instructions' => '',
            '--horde-dir' => '',
            '--pretend' => '',
            '--nodeps' => '',
            '--build-distribution' => '',
            '--sourcepath' => '',
            '--channelxmlpath' => '',
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
            $this->_dependencies->getRunnerInstaller()->run();
            return true;
        }
    }
}
