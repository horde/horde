<?php
/**
 * Components_Module_Help:: provides information for a single action.
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
 * Components_Module_Help:: provides information for a single action.
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
class Components_Module_Help
extends Components_Module_Base
{
    /**
     * Indicate if the module provides an option group.
     *
     * @return boolean True if an option group should be added.
     */
    public function hasOptionGroup()
    {
        return false;
    }

    public function getOptionGroupTitle()
    {
        return '';
    }

    public function getOptionGroupDescription()
    {
        return '';
    }

    public function getOptionGroupOptions()
    {
        return array();
    }

    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return '  help ACTION - Provide information about the specified ACTION.
';
    }

    /**
     * Return the action arguments supported by this module.
     *
     * @return array A list of supported action arguments.
     */
    public function getActions()
    {
        return array('help');
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
        $arguments = $config->getArguments();
        if (isset($arguments[0]) && $arguments[0] == 'help') {
            if (isset($arguments[1])) {
                $action = $arguments[1];
            } else {
                $action = '';
            }
            $modules = $this->_dependencies->getModules();
            foreach ($modules->getModules()->listModules() as $module) {
                $element = $modules->getProvider()->getModule($module);
                if (in_array($action, $element->getActions())) {
                    $title = "ACTION \"" . $action . "\"";
                    $sub = str_repeat('-', strlen($title));
                    $help = "\n" . $title . "\n" . $sub . "\n\n";
                    $help .= Horde_String::wordwrap(
                        $element->getHelp($action), 75, "\n", true
                    );
                    $options = $element->getContextOptionHelp();
                    if (!empty($options)) {
                        $formatter = new Horde_Argv_IndentedHelpFormatter();
                        $parser = $this->_dependencies->getParser();
                        $title = "OPTIONS for \"" . $action . "\"";
                        $sub = str_repeat('-', strlen($title));
                        $help .= "\n\n\n" . $title . "\n" . $sub . "";
                        foreach ($options as $option => $help_text) {
                            $argv_option = $parser->getOption($option);
                            $help .= "\n\n    " . $formatter->formatOptionStrings($argv_option) . "\n\n      ";
                            if (empty($help_text)) {
                                $help .= Horde_String::wordwrap(
                                    $argv_option->help, 75, "\n      ", true
                                );
                            } else {
                                $help .= Horde_String::wordwrap(
                                    $help_text, 75, "\n      ", true
                                );
                            }
                        }
                    }
                    $help .= "\n";
                    $this->_dependencies->getOutput()->help(
                        $help
                    );
                    return true;
                }
            }
            return false;
        }
    }
}
