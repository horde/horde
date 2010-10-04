<?php
/**
 * Components_Module_Distribute:: prepares a distribution package for a
 * component.
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
 * Components_Module_Distribute:: prepares a distribution package for a
 * component.
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
class Components_Module_Distribute
extends Components_Module_Base
{
    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return 'Distribute';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return 'This module prepares a distribution package (e.g. RPM, APT, Ebuild, ...) from a component.';
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
                '-D',
                '--distribute',
                array(
                    'action' => 'store',
                    'help'   => 'Prepare the component package in the specified DISTRIBUTE location'
                )
            ),
        );
    }

    /**
     * Determine if this module should act. Run all required actions if it has
     * been instructed to do so.
     *
     * @return NULL
     */
    public function handle(Components_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['distribute'])) {
            $this->_dependencies->getRunnerDistribute()->run();
        }
    }
}
