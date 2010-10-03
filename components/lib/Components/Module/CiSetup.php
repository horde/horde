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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Module_CiSetup:: generates the configuration for Hudson based
 * continuous integration of a Horde PEAR package.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
                '-c',
                '--cisetup',
                array(
                    'action' => 'store',
                    'help'   => 'generate the basic Hudson project configuration for a Horde PEAR package in CISETUP'
                )
            ),
            new Horde_Argv_Option(
                '-C',
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
            new Horde_Argv_Option(
                '-R',
                '--pearrc',
                array(
                    'action' => 'store',
                    'help'   => 'the path to the configuration of the PEAR installation'
                )
            ),
        );
    }

    public function handle(Components_Config $config)
    {
        $options = $config->getOptions();
        //@todo Split into two different runners here
        if (!empty($options['cisetup']) | !empty($options['ciprebuild'])) {
            $this->_dependencies->getRunnerCiSetup()->run();
        }
    }
}
