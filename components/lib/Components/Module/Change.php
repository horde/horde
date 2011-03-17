<?php
/**
 * Components_Module_Change:: records a change log entry.
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
 * Components_Module_Change:: records a change log entry.
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
                '-n',
                '--changed',
                array(
                    'action' => 'store',
                    'help'   => 'Store CHANGED as change log entry.'
                )
            ),
            new Horde_Argv_Option(
                '-G',
                '--commit',
                array(
                    'action' => 'store_true',
                    'help'   => 'Commit the change log entries to git (using the change log message).'
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
        return '  changed - Add a change log entry.
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
        return 'Action "changed"

This module records a change log entry in package.xml (and docs/CHANGES in case it exists).
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
        if (!empty($options['changed'])
            || (isset($arguments[0]) && $arguments[0] == 'changed')) {
            $this->requirePackageXml($config->getComponentDirectory());
            $this->_dependencies->getRunnerChange()->run();
            return true;
        }
    }
}
