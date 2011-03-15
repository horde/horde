<?php
/**
 * Components_Module_Release:: generates a release.
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
 * Components_Module_Release:: generates a release.
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
        );
    }

    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return '  release - Releases a component via pear.horde.org.
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
        return 'Action "release"

Releases the component. This handles a number of automated steps usually
required when releasing a package to pear.horde.org. In the most simple
situation it will be sufficient to move to the directory of the component
you with to release and run

 horde-components release

This should perform all required actions. Sometimes it might be necessary
to avoid some of the steps that are part of the release process. This can
be done by adding additional arguments after the "release" keyword. Each
argument indicates that the corresponding task should be run.

The available tasks are:

 - timestamp   : Update the package with a current timestamp
 - package     : Prepare a *.tgz package.
   - upload    : Upload the package to pear.horde.org
   - commit    : Commit the updated timestamp with an automated message.
   - tag       : Add a git release tag.

The indentation indicates task that depend on a parent task. Activating them
without activating the parent has no effect.

The following example would generate the package and add the release tag to
git without any other release task being performed:

 horde-components release package tag
';
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
            $this->requirePackageXml($config->getComponentDirectory());
            $this->_dependencies->getRunnerRelease()->run();
            return true;
        }
    }
}
