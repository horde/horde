<?php
/**
 * Components_Module_Release:: generates a release.
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
 * Components_Module_Release:: generates a release.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Components_Module_Release
extends Components_Module_Base
{
    public function getOptionGroupTitle()
    {
        return 'Package release';
    }

    public function getOptionGroupDescription()
    {
        return 'This module releases a new version for the specified package';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-r',
                '--release',
                array(
                    'action' => 'store_true',
                    'help'   => 'Release the next version of the package.'
                )
            ),
            new Horde_Argv_Option(
                '-M',
                '--releaseserver',
                array(
                    'action' => 'store',
                    'help'   => 'The remote server SSH connection string. The release package will be copied here via "scp".'
                )
            ),
            new Horde_Argv_Option(
                '-U',
                '--releasedir',
                array(
                    'action' => 'store',
                    'help'   => 'PEAR server target directory on the remote machine.'
                )
            ),
            new Horde_Argv_Option(
                '--next_version',
                array(
                    'action' => 'store',
                    'help'   => 'The version number planned for the next release of the component.'
                )
            ),
            new Horde_Argv_Option(
                '--next_note',
                array(
                    'action' => 'store',
                    'default' => '',
                    'help'   => 'Initial change log note for the next version of the component [default: empty entry].'
                )
            ),
            new Horde_Argv_Option(
                '--next_apistate',
                array(
                    'action' => 'store',
                    'help'   => 'The next API stability [default: no change].'
                )
            ),
            new Horde_Argv_Option(
                '--next_relstate',
                array(
                    'action' => 'store',
                    'help'   => 'The next release stability [default: no change].'
                )
            ),
            new Horde_Argv_Option(
                '--from',
                array(
                    'action' => 'store',
                    'help'   => 'The sender address for mailing list announcements.'
                )
            ),
            new Horde_Argv_Option(
                '--horde_user',
                array(
                    'action' => 'store',
                    'help'   => 'The username for accessing bugs.horde.org.'
                )
            ),
            new Horde_Argv_Option(
                '--horde_pass',
                array(
                    'action' => 'store',
                    'help'   => 'The password for accessing bugs.horde.org.'
                )
            ),
            new Horde_Argv_Option(
                '--fm_token',
                array(
                    'action' => 'store',
                    'help'   => 'The token for accessing freecode.com.'
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
        return '  release     - Releases a component via pear.horde.org.
';
    }

    /**
     * Return the action arguments supported by this module.
     *
     * @return array A list of supported action arguments.
     */
    public function getActions()
    {
        return array('release');
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
        return 'Releases the component. This handles a number of automated steps usually required when releasing a package to pear.horde.org. In the most simple situation it will be sufficient to move to the directory of the component you wish to release and run

  horde-components release

This should perform all required actions. Sometimes it might be necessary to avoid some of the steps that are part of the release process. This can be done by adding additional arguments after the "release" keyword. Each argument indicates that the corresponding task should be run.

The available tasks are:

 - timestamp   : Timestamp the package.xml and sync the change log.
 - sentinel    : Update the sentinels in docs/CHANGES and lib/Application.php.
 - commit      : Commit any changes with an automated message.
 - package     : Prepare a *.tgz package.
   - upload    : Upload the package to pear.horde.org
 - tag         : Add a git release tag.
 - announce    : Announce the release on the mailing lists.
 - bugs        : Add the new release on bugs.horde.org
 - freecode    : Add the new release on freecode.com
 - next        : Update package.xml with the next version.
 - nextsentinel: Update the sentinels for the next version as well.

The indentation indicates task that depend on a parent task. Activating them without activating the parent has no effect.

The following example would generate the package and add the release tag to git without any other release task being performed:

  horde-components release package tag';
    }

    /**
     * Return the options that should be explained in the context help.
     *
     * @return array A list of option help texts.
     */
    public function getContextOptionHelp()
    {
        return array(
            '--pretend' => '',
            '--config' => '',
            '--releaseserver' => '',
            '--releasedir' => '',
            '--next_note' => '',
            '--next_version' => '',
            '--next_relstate' => '',
            '--next_apistate' => '',
            '--from' => '',
            '--horde_user' => '',
            '--horde_pass' => '',
            '--fm_token' => '',
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
        if (!empty($options['release'])
            || (isset($arguments[0]) && $arguments[0] == 'release')) {
            $this->_dependencies->getRunnerRelease()->run();
            return true;
        }
    }
}
