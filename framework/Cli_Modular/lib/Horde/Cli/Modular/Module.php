<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Cli_Modular
 */

/**
 * The Horde_Cli_Modular_Module interface characterizes a single CLI module.
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Cli_Modular
 */
interface Horde_Cli_Modular_Module
{
    /**
     * Returns additional usage description for this module.
     *
     * This description will be added after the automatically generated usage
     * line, so make sure to add any necessary line breaks or other separators.
     *
     * @return string  The description.
     */
    public function getUsage();

    /**
     * Returns a set of base options that this module adds to the CLI argument
     * parser.
     *
     * @return array  Global options. A list of Horde_Argv_Option objects.
     */
    public function getBaseOptions();

    /**
     * Returns whether the module provides an option group.
     *
     * @return boolean  True if an option group should be added.
     */
    public function hasOptionGroup();

    /**
     * Returns the title for the option group representing this module.
     *
     * @return string  The group title.
     */
    public function getOptionGroupTitle();

    /**
     * Returns the description for the option group representing this module.
     *
     * @return string  The group description.
     */
    public function getOptionGroupDescription();

    /**
     * Returns the options for this module.
     *
     * @return array  The group options. A list of Horde_Argv_Option objects.
     */
    public function getOptionGroupOptions();
}