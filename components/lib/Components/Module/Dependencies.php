<?php
/**
 * Components_Module_Dependencies:: generates a dependency listing for the
 * specified package.
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
 * Components_Module_Dependencies:: generates a dependency listing for the
 * specified package.
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
class Components_Module_Dependencies
extends Components_Module_Base
{
    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return 'Package Dependencies';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return 'This module generates a list of dependencies for the specified package';
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
                '-L',
                '--list-deps',
                array(
                    'action' => 'store_true',
                    'help'   => 'generate a dependency listing'
                )
            ),
            new Horde_Argv_Option(
                '--short',
                array(
                    'action' => 'store_true',
                    'help'   => 'Generate a brief dependency list.'
                )
            ),
            new Horde_Argv_Option(
                '--alldeps',
                array(
                    'action' => 'store_true',
                    'help'   => 'Include all optional dependencies into the dependency list.'
                )
            ),
            new Horde_Argv_Option(
                '--no-tree',
                array(
                    'action' => 'store_true',
                    'help'   => 'Just print the dependencies of this package (YAML format) rather than generating a complete tree.'
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
        return '  deps        - Generate a dependency list.
';
    }

    /**
     * Return the action arguments supported by this module.
     *
     * @return array A list of supported action arguments.
     */
    public function getActions()
    {
        return array('deps');
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
        return 'This module generates a dependency tree for a component.';
    }

    /**
     * Return the options that should be explained in the context help.
     *
     * @return array A list of option help texts.
     */
    public function getContextOptionHelp()
    {
        return array(
            '--short' => '',
            '--alldeps' => '',
            '--no-tree' => '',
            '--allow-remote' => 'The dependency list should also resolve the dependency tree of components from remote channels.',
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
        if (!empty($options['list_deps'])
            || (isset($arguments[0]) && $arguments[0] == 'deps')) {
            $this->_dependencies->getRunnerDependencies()->run();
            return true;
        }
    }
}
