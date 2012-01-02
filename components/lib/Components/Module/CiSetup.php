<?php
/**
 * Components_Module_CiSetup:: generates the configuration for Hudson based
 * continuous integration of a Horde PEAR package.
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
 * Components_Module_CiSetup:: generates the configuration for Hudson based
 * continuous integration of a Horde PEAR package.
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
class Components_Module_CiSetup
extends Components_Module_Base
{
    public function getOptionGroupTitle()
    {
        return 'Continuous Integration Setup';
    }

    public function getOptionGroupDescription()
    {
        return 'This module generates the configuration for Hudson based continuous integration of a Horde PEAR package.';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '--cisetup',
                array(
                    'action' => 'store',
                    'help'   => 'generate the basic Hudson project configuration for a Horde PEAR package in CISETUP'
                )
            ),
            new Horde_Argv_Option(
                '--ciprebuild',
                array(
                    'action' => 'store',
                    'help'   => 'generate the Hudson build configuration for a Horde PEAR package in CIPREBUILD'
                )
            ),
            new Horde_Argv_Option(
                '-T',
                '--toolsdir',
                array(
                    'action' => 'store',
                    'help'   => 'the path to the PEAR installation holding the required analysis tools'
                )
            ),
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
        //@todo Split into two different runners here
        if (!empty($options['cisetup'])) {
            $this->_dependencies->getRunnerCiSetup()->run();
            return true;
        }
        if (!empty($options['ciprebuild'])) {
            $this->_dependencies->getRunnerCiPrebuild()->run();
            return true;
        }
    }
}
