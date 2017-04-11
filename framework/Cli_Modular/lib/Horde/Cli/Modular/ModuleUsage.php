<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Cli_Modular
 */

/**
 * The Horde_Cli_Modular_ModuleUsage interface extends the
 * Horde_Cli_Modular_Module interface with new functionality.
 *
 * This description will be added after the automatically generated usage line.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Cli_Modular
 * @todo      H6: Wrap into Horde_Cli_Modular_Module
 */
interface Horde_Cli_Modular_ModuleUsage
{
    /**
     * Returns additional usage title for this module.
     *
     * @return string  The usage title.
     */
    public function getTitle();

    /**
     * Returns additional usage description for this module.
     *
     * This description will be added after the automatically generated usage
     * line, no need to add any line breaks or other separators.
     *
     * @return string  The usage description.
     */
    public function getUsage();
}