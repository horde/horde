<?php
/**
 * Components_Module_Change:: records a change log entry.
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
 * Components_Module_Change:: records a change log entry.
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
class Components_Module_Change
extends Components_Module_Base
{
    public function getOptionGroupTitle()
    {
        return 'Change log';
    }

    public function getOptionGroupDescription()
    {
        return 'This module records a change log entry in package.xml (and docs/CHANGES in case it exists).';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '',
                '--nochanges',
                array(
                    'action' => 'store_true',
                    'help'   => 'Don\'t add entry to CHANGES.'
                )
            ),
            new Horde_Argv_Option(
                '',
                '--nopackage',
                array(
                    'action' => 'store_true',
                    'help'   => 'Don\'t add entry to package.xml.'
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
        return '  changed     - Add a change log entry.
';
    }

    /**
     * Return the action arguments supported by this module.
     *
     * @return array A list of supported action arguments.
     */
    public function getActions()
    {
        return array('changed');
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
        return 'This module records a change log entry in package.xml and
docs/CHANGES in case it exists.

Move into the directory of the component you wish to record a change for
and run

  horde-components changed "[xyz] Fixed issue #99999"

If you want to commit the change log entry immediately you can run the
command with the "--commit" flag:

  horde-components changed --commit "[xyz] Fixed issue #99999"

This will use the change log message as commit message. You might wish to
ensure that you already added all changes you want to mark with that
commit message by using "git add ..." before.';
    }

    /**
     * Return the options that should be explained in the context help.
     *
     * @return array A list of option help texts.
     */
    public function getContextOptionHelp()
    {
        return array(
            '--commit' => 'Commit the change log entries to git (using the change log entry as commit message).',
            '--nochanges' => '',
            '--nopackage' => '',
            '--pretend' => ''
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
        if (!empty($options['changed'])
            || (isset($arguments[0]) && $arguments[0] == 'changed')) {
            $this->_dependencies->getRunnerChange()->run();
            return true;
        }
    }
}
