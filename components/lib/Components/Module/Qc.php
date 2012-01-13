<?php
/**
 * Components_Module_Qc:: checks the component for quality.
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
 * Components_Module_Qc:: checks the component for quality.
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
class Components_Module_Qc
extends Components_Module_Base
{
    public function getOptionGroupTitle()
    {
        return 'Package quality control';
    }

    public function getOptionGroupDescription()
    {
        return 'This module runs a quality control check for the specified package.';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-Q',
                '--qc',
                array(
                    'action' => 'store_true',
                    'help'   => 'Check the package quality.'
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
        return '  qc          - Check the package quality.
';
    }

    /**
     * Return the action arguments supported by this module.
     *
     * @return array A list of supported action arguments.
     */
    public function getActions()
    {
        return array('qc');
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
        return 'Runs quality control checks for the component. This executes a number of automated quality control checks that are similar to the checks you find on ci.horde.org. In the most simple situation it will be sufficient to move to the directory of the component you wish to release and run

  horde-components qc

This will run all available checks. You can also choose to execute only some of the quality control checks. For that you need to indicate the desired checks after the "qc" keyword. Each argument indicates that the corresponding check should be run.

The available checks are:

 - unit: Runs the PHPUnit unit test suite of the component.
 - md  : Runs the PHP mess detector on the code of the component.
 - cs  : Runs a checkstyle analysis of the component.
 - cpd : Checks for copied segments within the component.
 - lint: Runs a lint check of the source code.

The following example would solely run the PHPUnit test for the package:

  horde-components qc unit';
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
        if (!empty($options['qc'])
            || (isset($arguments[0]) && $arguments[0] == 'qc')) {
            $this->_dependencies->getRunnerQc()->run();
            return true;
        }
    }
}
