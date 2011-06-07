<?php
/**
 * Components_Module_Update:: can update the package.xml of
 * a Horde element.
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
 * Components_Module_Update:: can update the package.xml of
 * a Horde element.
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
class Components_Module_Update
extends Components_Module_Base
{
    public function getOptionGroupTitle()
    {
        return 'Update ppackage.xml';
    }

    public function getOptionGroupDescription()
    {
        return 'This module allows updating of the package.xml.';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-u',
                '--updatexml',
                array(
                    'action' => 'store_true',
                    'help'   => 'Update the package.xml for the package'
                )
            ),
            new Horde_Argv_Option(
                '-A',
                '--action',
                array(
                    'action'  => 'store',
                    'type'    => 'choice',
                    'choices' => array('update', 'diff', 'print'),
                    'default' => 'update',
                    'help'    => 'An optional argument to "--updatexml" that allows choosing the action that should be performed. The default is "update" which will rewrite the package.xml. "diff" allows you to produce a diffed output of the changes that would be applied with "update" - the "Horde_Text_Diff" package needs to be installed for that. "print" will output the new package.xml to the screen rather than rewriting it.'
                )
            ),
            new Horde_Argv_Option(
                '--regenerate',
                array(
                    'action' => 'store_true',
                    'help'   => 'Replace the old lists with a fresh listing.'
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
        return '  update      - Update the package.xml manifest.
';
    }

    /**
     * Return the action arguments supported by this module.
     *
     * @return array A list of supported action arguments.
     */
    public function getActions()
    {
        return array('update');
    }

    /**
     * Return the options that should be explained in the context help.
     *
     * @return array A list of option help texts.
     */
    public function getContextOptionHelp()
    {
        return array(
            '--pretend' => 'Display a textual diff of the current package.xml and the updated package.xml. The package.xml file does not get modified.',
            '--regenerate' => 'Purge the old file listings and replace them with a completely fresh list.',
        );
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
        return 'This will automatically update the package.xml of the specified component to include any new files that were added/removed since the package.xml was modified last time.';
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
        if (!empty($options['updatexml'])
            || (isset($arguments[0]) && $arguments[0] == 'update')) {
            $config->getComponent()->requireLocal();
            $this->_dependencies->getRunnerUpdate()->run();
            return true;
        }
    }
}
