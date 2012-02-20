<?php
/**
 * Components_Module_Webdocs:: generates the www.horde.org data for a component.
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
 * Components_Module_Webdocs:: generates the www.horde.org data for a component.
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
class Components_Module_Webdocs
extends Components_Module_Base
{
    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return 'Generate website documentation';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return 'This module generates the www.horde.org data for the component.';
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
                '-W',
                '--webdocs',
                array(
                    'action' => 'store_true',
                    'help'   => 'Generate the documentation for the component in the specified DESTINATION or WEBSOURCE location.'
                )
            ),
            new Horde_Argv_Option(
                '--html-generator',
                array(
                    'action' => 'store',
                    'help'   => 'Path to the Python docutils HTML generator script.'
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
        return '  webdocs     - Generate documentation for www.horde.org.
';
    }

    /**
     * Return the action arguments supported by this module.
     *
     * @return array A list of supported action arguments.
     */
    public function getActions()
    {
        return array('webdocs');
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
        return 'This module generates the required set of data to publish information about this component on www.horde.org. The operation will only work with an already relased package! Make sure you enter the name of the package on the PEAR server rather than using a local path and ensure you added the "--allow-remote" flag as well.';
    }

    /**
     * Return the options that should be explained in the context help.
     *
     * @return array A list of option help texts.
     */
    public function getContextOptionHelp()
    {
        return array(
            '--destination' => 'The documentation for the component will be written to the location specified as DESTINATION. The module will assume DESTINATION is a checkout of the "horde-web" git repository.',
            '--html-generator' => '',
            '--pretend' => '',
            '--allow-remote' => '',
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
        if (!empty($options['webdocs'])
            || (isset($arguments[0]) && $arguments[0] == 'webdocs')) {
            $this->_dependencies->getRunnerWebdocs()->run();
            return true;
        }
    }
}
